<?php

namespace App\Payments\Contracts;

use App\Models\Sale;
use App\Payments\DTOs\PaymentCheckoutResult;
use App\Payments\DTOs\PaymentStatusResult;
use App\Payments\DTOs\PaymentWebhookPayload;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(): bool;

    public function supportsRedirectCheckout(): bool;

    /**
     * @return array<int, string>
     */
    public function supportedMethods(): array;

    /**
     * @param array<string, mixed> $options
     */
    public function createCheckout(Sale $sale, string $redirectUrl, array $options = []): PaymentCheckoutResult;

    public function getTransactionStatus(Sale $sale): PaymentStatusResult;

    public function cancelTransaction(Sale $sale): bool;

    public function parseWebhook(Request $request): PaymentWebhookPayload;
}
