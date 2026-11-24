<?php

namespace App\Payments\Drivers;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Payments\Concerns\ReadsGatewaySettings;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentCheckoutResult;
use App\Payments\DTOs\PaymentStatusResult;
use App\Payments\DTOs\PaymentWebhookPayload;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MidtransGateway implements PaymentGatewayInterface
{
    use ReadsGatewaySettings;

    public function key(): string
    {
        return 'midtrans';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.midtrans.label', 'Midtrans Snap');
    }

    public function isConfigured(): bool
    {
        $config = $this->resolveConfig();

        return !empty($config['server_key']);
    }

    public function supportsRedirectCheckout(): bool
    {
        return true;
    }

    public function supportedMethods(): array
    {
        return [
            PaymentMethod::QRIS->value,
            PaymentMethod::EWALLET->value,
            PaymentMethod::VIRTUAL_ACCOUNT->value,
            PaymentMethod::TRANSFER->value,
        ];
    }

    public function createCheckout(Sale $sale, string $redirectUrl, array $options = []): PaymentCheckoutResult
    {
        $config = $this->getRequiredConfig();
        $snapBase = $config['is_production']
            ? config('payment.gateways.midtrans.snap_production_url', 'https://app.midtrans.com')
            : config('payment.gateways.midtrans.snap_sandbox_url', 'https://app.sandbox.midtrans.com');

        $payload = [
            'transaction_details' => [
                'order_id' => $sale->invoice_number,
                'gross_amount' => (int) $sale->total,
            ],
            'callbacks' => [
                'finish' => $redirectUrl,
            ],
        ];

        $enabledPayments = $this->mapEnabledPayments($options['method'] ?? $sale->payment_method->value);
        if (!empty($enabledPayments)) {
            $payload['enabled_payments'] = $enabledPayments;
        }

        if ($sale->relationLoaded('customer') && $sale->customer) {
            $payload['customer_details'] = array_filter([
                'first_name' => $sale->customer->name,
                'email' => $sale->customer->email,
                'phone' => $sale->customer->phone,
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withBasicAuth((string) $config['server_key'], '')
                ->timeout(15)
                ->post(rtrim((string) $snapBase, '/') . '/snap/v1/transactions', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans Snap.', previous: $exception);
        }

        $data = $response->json() ?? [];

        return new PaymentCheckoutResult(
            gateway: $this->key(),
            reference: $sale->invoice_number,
            checkoutUrl: $data['redirect_url'] ?? null,
            status: 'pending',
            requiresRedirect: !empty($data['redirect_url']),
            meta: [
                'token' => $data['token'] ?? null,
                'is_production' => $config['is_production'],
            ],
        );
    }

    public function getTransactionStatus(Sale $sale): PaymentStatusResult
    {
        $config = $this->getRequiredConfig();
        $apiBase = $config['is_production']
            ? config('payment.gateways.midtrans.api_production_url', 'https://api.midtrans.com')
            : config('payment.gateways.midtrans.api_sandbox_url', 'https://api.sandbox.midtrans.com');

        try {
            $response = Http::acceptJson()
                ->withBasicAuth((string) $config['server_key'], '')
                ->timeout(15)
                ->get(rtrim((string) $apiBase, '/') . '/v2/' . urlencode($sale->invoice_number) . '/status')
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal memeriksa status transaksi Midtrans.', previous: $exception);
        }

        $data = $response->json() ?? [];

        return new PaymentStatusResult(
            gateway: $this->key(),
            reference: (string) ($data['transaction_id'] ?? $sale->invoice_number),
            status: $this->mapStatus(
                (string) ($data['transaction_status'] ?? 'pending'),
                $data['fraud_status'] ?? null
            ),
            amount: (int) round((float) ($data['gross_amount'] ?? $sale->total)),
            paymentMethod: $this->normalizeMethod($data['payment_type'] ?? null),
            raw: $data,
        );
    }

    public function cancelTransaction(Sale $sale): bool
    {
        $config = $this->getRequiredConfig();
        $apiBase = $config['is_production']
            ? config('payment.gateways.midtrans.api_production_url', 'https://api.midtrans.com')
            : config('payment.gateways.midtrans.api_sandbox_url', 'https://api.sandbox.midtrans.com');

        try {
            Http::acceptJson()
                ->withBasicAuth((string) $config['server_key'], '')
                ->timeout(15)
                ->post(rtrim((string) $apiBase, '/') . '/v2/' . urlencode($sale->invoice_number) . '/cancel')
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal membatalkan transaksi Midtrans.', previous: $exception);
        }

        return true;
    }

    public function parseWebhook(Request $request): PaymentWebhookPayload
    {
        $payload = $request->validate([
            'order_id' => ['required', 'string', 'max:100'],
            'status_code' => ['required', 'string', 'max:20'],
            'gross_amount' => ['required', 'string', 'max:50'],
            'signature_key' => ['required', 'string', 'max:256'],
            'transaction_status' => ['required', 'string', 'max:50'],
            'fraud_status' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'payment_type' => ['nullable', 'string', 'max:50'],
        ]);

        $config = $this->getRequiredConfig();
        $expectedSignature = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $config['server_key']
        );

        if (!hash_equals($expectedSignature, (string) $payload['signature_key'])) {
            throw new RuntimeException('Invalid Midtrans webhook signature.');
        }

        return new PaymentWebhookPayload(
            gateway: $this->key(),
            orderId: (string) $payload['order_id'],
            reference: (string) ($payload['transaction_id'] ?? $payload['order_id']),
            status: $this->mapStatus(
                (string) $payload['transaction_status'],
                $payload['fraud_status'] ?? null
            ),
            amount: (int) round((float) $payload['gross_amount']),
            paymentMethod: $this->normalizeMethod($payload['payment_type'] ?? null),
            verified: true,
            raw: $payload,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function mapEnabledPayments(string $method): array
    {
        return match ($method) {
            PaymentMethod::QRIS->value => ['gopay', 'other_qris', 'shopeepay'],
            PaymentMethod::EWALLET->value => ['gopay', 'shopeepay', 'dana'],
            PaymentMethod::VIRTUAL_ACCOUNT->value => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel'],
            default => [],
        };
    }

    protected function mapStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        $status = strtolower($transactionStatus);

        if ($status === 'capture') {
            return strtolower((string) $fraudStatus) === 'challenge' ? 'pending' : 'paid';
        }

        return match ($status) {
            'settlement' => 'paid',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }

    protected function normalizeMethod(?string $paymentType): ?string
    {
        if (!$paymentType) {
            return null;
        }

        return match (strtolower($paymentType)) {
            'qris', 'other_qris' => PaymentMethod::QRIS->value,
            'gopay', 'shopeepay', 'dana', 'ovo' => PaymentMethod::EWALLET->value,
            'bank_transfer', 'echannel', 'permata', 'bca_va', 'bni_va', 'bri_va' => PaymentMethod::VIRTUAL_ACCOUNT->value,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequiredConfig(): array
    {
        $config = $this->resolveConfig();

        if (empty($config['server_key'])) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'Konfigurasi Midtrans Server Key belum lengkap (isi di Settings atau .env).',
            ]);
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $base = config('payment.gateways.midtrans', []);

        return [
            'server_key' => $this->settingOrFallback('payment_midtrans_server_key', $base['server_key'] ?? null),
            'client_key' => $this->settingOrFallback('payment_midtrans_client_key', $base['client_key'] ?? null),
            'is_production' => $this->settingBoolOrFallback('payment_midtrans_is_production', (bool) ($base['is_production'] ?? false)),
        ];
    }
}
