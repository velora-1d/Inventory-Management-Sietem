<?php

namespace App\Payments\Drivers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentCheckoutResult;
use App\Payments\DTOs\PaymentStatusResult;
use App\Payments\DTOs\PaymentWebhookPayload;
use Illuminate\Http\Request;
use RuntimeException;

class ManualGateway implements PaymentGatewayInterface
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.manual.label', 'Manual / Offline');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function supportsRedirectCheckout(): bool
    {
        return false;
    }

    public function supportedMethods(): array
    {
        return array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases());
    }

    public function createCheckout(Sale $sale, string $redirectUrl, array $options = []): PaymentCheckoutResult
    {
        return new PaymentCheckoutResult(
            gateway: $this->key(),
            reference: $sale->invoice_number,
            checkoutUrl: null,
            status: $sale->status->value,
            requiresRedirect: false,
            meta: ['mode' => 'manual'],
        );
    }

    public function getTransactionStatus(Sale $sale): PaymentStatusResult
    {
        $status = match ($sale->status) {
            SaleStatus::COMPLETED => 'paid',
            SaleStatus::CANCELLED => 'cancelled',
            default => 'pending',
        };

        return new PaymentStatusResult(
            gateway: $this->key(),
            reference: $sale->payment_reference ?: $sale->invoice_number,
            status: $status,
            amount: (int) $sale->total,
            paymentMethod: $sale->payment_method->value,
            raw: ['mode' => 'manual'],
        );
    }

    public function cancelTransaction(Sale $sale): bool
    {
        return true;
    }

    public function parseWebhook(Request $request): PaymentWebhookPayload
    {
        throw new RuntimeException('Manual payment gateway does not accept external webhooks.');
    }
}
