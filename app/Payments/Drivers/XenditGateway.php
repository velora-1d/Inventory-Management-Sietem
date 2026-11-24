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

class XenditGateway implements PaymentGatewayInterface
{
    use ReadsGatewaySettings;

    public function key(): string
    {
        return 'xendit';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.xendit.label', 'Xendit Invoice');
    }

    public function isConfigured(): bool
    {
        $config = $this->resolveConfig();

        return !empty($config['secret_key']);
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

        $payload = [
            'external_id' => $sale->invoice_number,
            'amount' => (int) $sale->total,
            'description' => 'Pembayaran Invoice ' . $sale->invoice_number,
            'success_redirect_url' => $redirectUrl,
            'failure_redirect_url' => $redirectUrl,
            'currency' => 'IDR',
        ];

        $paymentMethods = $this->mapPaymentMethods($options['method'] ?? $sale->payment_method->value);
        if (!empty($paymentMethods)) {
            $payload['payment_methods'] = $paymentMethods;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withBasicAuth((string) $config['secret_key'], '')
                ->timeout(15)
                ->post(rtrim((string) $config['base_url'], '/') . '/v2/invoices', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal membuat invoice Xendit.', previous: $exception);
        }

        $data = $response->json() ?? [];

        return new PaymentCheckoutResult(
            gateway: $this->key(),
            reference: (string) ($data['id'] ?? $sale->invoice_number),
            checkoutUrl: $data['invoice_url'] ?? null,
            status: 'pending',
            requiresRedirect: !empty($data['invoice_url']),
            meta: [
                'invoice_id' => $data['id'] ?? null,
                'external_id' => $data['external_id'] ?? $sale->invoice_number,
            ],
        );
    }

    public function getTransactionStatus(Sale $sale): PaymentStatusResult
    {
        $config = $this->getRequiredConfig();

        try {
            if ($sale->payment_reference && $sale->payment_reference !== $sale->invoice_number) {
                $response = Http::acceptJson()
                    ->withBasicAuth((string) $config['secret_key'], '')
                    ->timeout(15)
                    ->get(rtrim((string) $config['base_url'], '/') . '/v2/invoices/' . urlencode($sale->payment_reference))
                    ->throw();
                $invoice = $response->json() ?? [];
            } else {
                $response = Http::acceptJson()
                    ->withBasicAuth((string) $config['secret_key'], '')
                    ->timeout(15)
                    ->get(rtrim((string) $config['base_url'], '/') . '/v2/invoices', [
                        'external_id' => $sale->invoice_number,
                    ])
                    ->throw();
                $list = $response->json() ?? [];
                $invoice = is_array($list) && isset($list[0]) ? $list[0] : [];
            }
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal memeriksa status invoice Xendit.', previous: $exception);
        }

        return new PaymentStatusResult(
            gateway: $this->key(),
            reference: (string) ($invoice['id'] ?? $sale->invoice_number),
            status: $this->mapStatus((string) ($invoice['status'] ?? 'PENDING')),
            amount: (int) round((float) ($invoice['amount'] ?? $sale->total)),
            paymentMethod: $this->normalizeMethod($invoice['payment_method'] ?? null),
            raw: $invoice,
        );
    }

    public function cancelTransaction(Sale $sale): bool
    {
        $config = $this->getRequiredConfig();
        $invoiceId = $sale->payment_reference ?: ($sale->payment_meta['invoice_id'] ?? null);

        if (!$invoiceId) {
            return true;
        }

        try {
            Http::acceptJson()
                ->withBasicAuth((string) $config['secret_key'], '')
                ->timeout(15)
                ->post(rtrim((string) $config['base_url'], '/') . '/invoices/' . urlencode((string) $invoiceId) . '/expire!')
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal membatalkan invoice Xendit.', previous: $exception);
        }

        return true;
    }

    public function parseWebhook(Request $request): PaymentWebhookPayload
    {
        $config = $this->resolveConfig();
        $expectedToken = (string) ($config['callback_token'] ?? '');

        if ($expectedToken !== '') {
            $incomingToken = (string) $request->header('x-callback-token', '');
            if (!hash_equals($expectedToken, $incomingToken)) {
                throw new RuntimeException('Invalid Xendit callback token.');
            }
        }

        $payload = $request->validate([
            'external_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'id' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
        ]);

        return new PaymentWebhookPayload(
            gateway: $this->key(),
            orderId: (string) $payload['external_id'],
            reference: (string) ($payload['id'] ?? $payload['external_id']),
            status: $this->mapStatus((string) $payload['status']),
            amount: (int) round((float) $payload['amount']),
            paymentMethod: $this->normalizeMethod($payload['payment_method'] ?? $payload['payment_channel'] ?? null),
            verified: $expectedToken !== '',
            raw: $payload,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function mapPaymentMethods(string $method): array
    {
        return match ($method) {
            PaymentMethod::QRIS->value => ['QRIS'],
            PaymentMethod::EWALLET->value => ['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA'],
            PaymentMethod::VIRTUAL_ACCOUNT->value => ['BCA', 'BNI', 'BRI', 'MANDIRI', 'PERMATA'],
            default => [],
        };
    }

    protected function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID', 'SETTLED' => 'paid',
            'EXPIRED' => 'expired',
            'VOIDED', 'CANCELLED' => 'cancelled',
            'FAILED' => 'failed',
            default => 'pending',
        };
    }

    protected function normalizeMethod(?string $method): ?string
    {
        if (!$method) {
            return null;
        }

        return match (strtoupper($method)) {
            'QRIS' => PaymentMethod::QRIS->value,
            'EWALLET', 'OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA' => PaymentMethod::EWALLET->value,
            'BANK_TRANSFER', 'VIRTUAL_ACCOUNT' => PaymentMethod::VIRTUAL_ACCOUNT->value,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequiredConfig(): array
    {
        $config = $this->resolveConfig();

        if (empty($config['secret_key'])) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'Konfigurasi Xendit Secret Key belum lengkap (isi di Settings atau .env).',
            ]);
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $base = config('payment.gateways.xendit', []);

        return [
            'secret_key' => $this->settingOrFallback('payment_xendit_secret_key', $base['secret_key'] ?? null),
            'callback_token' => $this->settingOrFallback('payment_xendit_callback_token', $base['callback_token'] ?? null),
            'base_url' => (string) ($base['base_url'] ?? 'https://api.xendit.co'),
        ];
    }
}
