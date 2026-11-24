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

class PakasirGateway implements PaymentGatewayInterface
{
    use ReadsGatewaySettings;
    public function key(): string
    {
        return 'pakasir';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.pakasir.label', 'Pakasir Payment Gateway');
    }

    public function isConfigured(): bool
    {
        $config = $this->resolveConfig();

        return !empty($config['api_key']) && !empty($config['slug']) && !empty($config['base_url']);
    }

    public function supportsRedirectCheckout(): bool
    {
        return true;
    }

    public function supportedMethods(): array
    {
        return [
            PaymentMethod::QRIS->value,
            PaymentMethod::VIRTUAL_ACCOUNT->value,
            PaymentMethod::EWALLET->value,
        ];
    }

    public function createCheckout(Sale $sale, string $redirectUrl, array $options = []): PaymentCheckoutResult
    {
        $config = $this->getRequiredConfig();
        $method = $options['method'] ?? $sale->payment_method->value;

        $params = [
            'order_id' => $sale->invoice_number,
            'redirect' => $redirectUrl,
        ];

        if ($method === PaymentMethod::QRIS->value) {
            $params['qris_only'] = 1;
        }

        $checkoutUrl = sprintf(
            '%s/pay/%s/%d?%s',
            rtrim((string) $config['base_url'], '/'),
            (string) $config['slug'],
            (int) $sale->total,
            http_build_query($params),
        );

        return new PaymentCheckoutResult(
            gateway: $this->key(),
            reference: $sale->invoice_number,
            checkoutUrl: $checkoutUrl,
            status: 'pending',
            requiresRedirect: true,
            meta: ['slug' => $config['slug']],
        );
    }

    public function getTransactionStatus(Sale $sale): PaymentStatusResult
    {
        $config = $this->getRequiredConfig();

        $response = $this->sendJsonRequest('GET', '/api/transactiondetail', [
            'project' => $config['slug'],
            'amount' => (int) $sale->total,
            'order_id' => $sale->invoice_number,
            'api_key' => $config['api_key'],
        ]);

        $transaction = $response['transaction'] ?? [];
        $rawStatus = strtolower((string) ($transaction['status'] ?? 'pending'));

        return new PaymentStatusResult(
            gateway: $this->key(),
            reference: (string) ($transaction['order_id'] ?? $sale->invoice_number),
            status: $this->mapStatus($rawStatus),
            amount: (int) ($transaction['amount'] ?? $sale->total),
            paymentMethod: isset($transaction['payment_method']) ? strtolower((string) $transaction['payment_method']) : null,
            raw: $response,
        );
    }

    public function cancelTransaction(Sale $sale): bool
    {
        $config = $this->getRequiredConfig();

        $this->sendJsonRequest('POST', '/api/transactioncancel', [
            'project' => $config['slug'],
            'amount' => (int) $sale->total,
            'order_id' => $sale->invoice_number,
            'api_key' => $config['api_key'],
        ]);

        return true;
    }

    public function parseWebhook(Request $request): PaymentWebhookPayload
    {
        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'order_id' => ['required', 'string', 'max:100'],
            'project' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $config = $this->getRequiredConfig();
        $expectedAmount = (int) $payload['amount'];

        $detail = $this->sendJsonRequest('GET', '/api/transactiondetail', [
            'project' => $config['slug'],
            'amount' => $expectedAmount,
            'order_id' => $payload['order_id'],
            'api_key' => $config['api_key'],
        ]);

        $transaction = $detail['transaction'] ?? null;
        if (!$transaction) {
            throw new RuntimeException($this->displayName() . ' transaction detail not found via API.');
        }

        if (($transaction['project'] ?? null) !== $config['slug']) {
            throw new RuntimeException($this->displayName() . ' project slug mismatch.');
        }

        $verifiedAmount = (int) ($transaction['amount'] ?? 0);
        if ($verifiedAmount !== $expectedAmount) {
            throw new RuntimeException($this->displayName() . ' transaction amount mismatch.');
        }

        $rawStatus = strtolower((string) ($transaction['status'] ?? $payload['status']));

        return new PaymentWebhookPayload(
            gateway: $this->key(),
            orderId: (string) $payload['order_id'],
            reference: (string) ($transaction['order_id'] ?? $payload['order_id']),
            status: $this->mapStatus($rawStatus),
            amount: $verifiedAmount,
            paymentMethod: isset($transaction['payment_method']) ? strtolower((string) $transaction['payment_method']) : ($payload['payment_method'] ?? null),
            verified: true,
            raw: $detail,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function sendJsonRequest(string $method, string $path, array $payload): array
    {
        $config = $this->getRequiredConfig();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->send($method, rtrim((string) $config['base_url'], '/') . $path, [
                    $method === 'GET' ? 'query' : 'json' => $payload,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal menghubungi gateway ' . $this->displayName() . '.', previous: $exception);
        }

        return $response->json() ?? [];
    }

    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'completed', 'paid', 'settled', 'success' => 'paid',
            'cancelled', 'canceled' => 'cancelled',
            'expired', 'expire' => 'expired',
            'failed', 'deny' => 'failed',
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequiredConfig(): array
    {
        $config = $this->resolveConfig();

        if (empty($config['api_key']) || empty($config['slug']) || empty($config['base_url'])) {
            throw ValidationException::withMessages([
                'payment_gateway' => 'Konfigurasi ' . $this->displayName() . ' belum lengkap (isi di Settings atau .env).',
            ]);
        }

        return $config;
    }

    protected function displayName(): string
    {
        return 'Pakasir';
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $base = config('payment.gateways.pakasir', config('services.pakasir', []));

        return [
            'api_key' => $this->settingOrFallback('payment_pakasir_api_key', $base['api_key'] ?? config('services.pakasir.api_key')),
            'slug' => $this->settingOrFallback('payment_pakasir_slug', $base['slug'] ?? config('services.pakasir.slug')),
            'base_url' => $this->settingOrFallback('payment_pakasir_base_url', $base['base_url'] ?? config('services.pakasir.base_url', 'https://app.pakasir.com')),
        ];
    }
}
