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

class GenericWebhookGateway implements PaymentGatewayInterface
{
    use ReadsGatewaySettings;

    public function key(): string
    {
        return 'custom';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.custom.label', 'Custom / Generic Webhook Gateway');
    }

    public function isConfigured(): bool
    {
        $config = $this->resolveConfig();

        return !empty($config['checkout_url_template']);
    }

    public function supportsRedirectCheckout(): bool
    {
        return true;
    }

    public function supportedMethods(): array
    {
        return array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases());
    }

    public function createCheckout(Sale $sale, string $redirectUrl, array $options = []): PaymentCheckoutResult
    {
        $config = $this->resolveConfig();
        $template = (string) ($config['checkout_url_template'] ?? '');

        if ($template === '') {
            throw ValidationException::withMessages([
                'payment_gateway' => 'URL Checkout Gateway Custom belum dikonfigurasi (isi di Settings atau .env).',
            ]);
        }

        $method = (string) ($options['method'] ?? $sale->payment_method->value);
        $checkoutUrl = strtr($template, [
            '{order_id}' => urlencode($sale->invoice_number),
            '{amount}' => (string) ((int) $sale->total),
            '{redirect_url}' => urlencode($redirectUrl),
            '{method}' => urlencode($method),
        ]);

        return new PaymentCheckoutResult(
            gateway: $this->key(),
            reference: $sale->invoice_number,
            checkoutUrl: $checkoutUrl,
            status: 'pending',
            requiresRedirect: true,
            meta: ['template_resolved' => true],
        );
    }

    public function getTransactionStatus(Sale $sale): PaymentStatusResult
    {
        $config = $this->resolveConfig();
        $statusTemplate = (string) ($config['status_url_template'] ?? '');

        if ($statusTemplate === '') {
            return new PaymentStatusResult(
                gateway: $this->key(),
                reference: $sale->payment_reference ?: $sale->invoice_number,
                status: 'pending',
                amount: (int) $sale->total,
                paymentMethod: $sale->payment_method->value,
            );
        }

        $url = strtr($statusTemplate, [
            '{order_id}' => urlencode($sale->invoice_number),
            '{reference}' => urlencode((string) ($sale->payment_reference ?: $sale->invoice_number)),
            '{amount}' => (string) ((int) $sale->total),
        ]);

        try {
            $request = Http::acceptJson()->timeout(15);
            if (!empty($config['api_key'])) {
                $request = $request->withToken((string) $config['api_key']);
            }
            $response = $request->get($url)->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal memeriksa status gateway custom.', previous: $exception);
        }

        $data = $response->json() ?? [];

        return new PaymentStatusResult(
            gateway: $this->key(),
            reference: (string) ($data['reference'] ?? $data['reference_id'] ?? $sale->invoice_number),
            status: $this->mapStatus((string) ($data['status'] ?? 'pending')),
            amount: (int) round((float) ($data['amount'] ?? $data['gross_amount'] ?? $sale->total)),
            paymentMethod: $data['payment_method'] ?? null,
            raw: $data,
        );
    }

    public function cancelTransaction(Sale $sale): bool
    {
        $config = $this->resolveConfig();
        $cancelTemplate = (string) ($config['cancel_url_template'] ?? '');

        if ($cancelTemplate === '') {
            return true;
        }

        $url = strtr($cancelTemplate, [
            '{order_id}' => urlencode($sale->invoice_number),
            '{reference}' => urlencode((string) ($sale->payment_reference ?: $sale->invoice_number)),
            '{amount}' => (string) ((int) $sale->total),
        ]);

        try {
            $request = Http::acceptJson()->timeout(15);
            if (!empty($config['api_key'])) {
                $request = $request->withToken((string) $config['api_key']);
            }
            $request->post($url, [
                'order_id' => $sale->invoice_number,
                'amount' => (int) $sale->total,
            ])->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal membatalkan transaksi pada gateway custom.', previous: $exception);
        }

        return true;
    }

    public function parseWebhook(Request $request): PaymentWebhookPayload
    {
        $config = $this->resolveConfig();
        $secret = (string) ($config['webhook_secret'] ?? '');
        $verified = false;

        if ($secret !== '') {
            $headerName = (string) ($config['header_name'] ?: 'X-Webhook-Secret');
            $headerSecret = (string) $request->header($headerName, '');
            $hmacHeader = (string) $request->header('X-Signature', '');
            $expectedHmac = hash_hmac('sha256', $request->getContent(), $secret);

            if ($headerSecret !== '' && hash_equals($secret, $headerSecret)) {
                $verified = true;
            } elseif ($hmacHeader !== '' && hash_equals($expectedHmac, $hmacHeader)) {
                $verified = true;
            } else {
                throw new RuntimeException('Invalid custom gateway webhook signature or secret.');
            }
        }

        $data = $request->all();
        $orderId = (string) ($data['order_id'] ?? $data['invoice_number'] ?? $data['external_id'] ?? $data['reference_id'] ?? '');
        $status = (string) ($data['status'] ?? $data['transaction_status'] ?? '');
        $amount = $data['amount'] ?? $data['gross_amount'] ?? null;

        if ($orderId === '' || $status === '' || !is_numeric($amount)) {
            throw ValidationException::withMessages([
                'payload' => 'Webhook payload harus memiliki order_id/invoice_number, status, dan amount yang valid.',
            ]);
        }

        return new PaymentWebhookPayload(
            gateway: $this->key(),
            orderId: $orderId,
            reference: (string) ($data['reference'] ?? $data['transaction_id'] ?? $orderId),
            status: $this->mapStatus($status),
            amount: (int) round((float) $amount),
            paymentMethod: isset($data['payment_method']) ? strtolower((string) $data['payment_method']) : null,
            verified: $verified,
            raw: $data,
        );
    }

    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'completed', 'paid', 'settled', 'settlement', 'success' => 'paid',
            'cancelled', 'canceled', 'voided' => 'cancelled',
            'expired', 'expire' => 'expired',
            'failed', 'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $base = config('payment.gateways.custom', []);

        return [
            'checkout_url_template' => $this->settingOrFallback('payment_custom_checkout_url', $base['checkout_url_template'] ?? null),
            'status_url_template' => $this->settingOrFallback('payment_custom_status_url', $base['status_url_template'] ?? null),
            'cancel_url_template' => $this->settingOrFallback('payment_custom_cancel_url', $base['cancel_url_template'] ?? null),
            'api_key' => $this->settingOrFallback('payment_custom_api_key', $base['api_key'] ?? null),
            'webhook_secret' => $this->settingOrFallback('payment_custom_webhook_secret', $base['webhook_secret'] ?? null),
            'header_name' => $base['header_name'] ?? 'X-Webhook-Secret',
        ];
    }
}
