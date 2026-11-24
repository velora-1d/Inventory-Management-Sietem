<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Payments\Drivers\PakasirGateway;

class PakasirService
{
    public function __construct(
        protected ?PakasirGateway $gateway = null
    ) {
        $this->gateway ??= app(PakasirGateway::class);
    }

    public function buildGatewayCheckoutUrl(string $orderId, int $amount, string $redirectUrl, string $method): string
    {
        $sale = new Sale([
            'invoice_number' => $orderId,
            'total' => $amount,
            'payment_method' => PaymentMethod::tryFrom($method) ?? PaymentMethod::QRIS,
        ]);

        return (string) $this->gateway->createCheckout($sale, $redirectUrl, ['method' => $method])->checkoutUrl;
    }

    public function getTransactionDetail(string $orderId, int $amount): array
    {
        $sale = new Sale([
            'invoice_number' => $orderId,
            'total' => $amount,
        ]);

        return $this->gateway->getTransactionStatus($sale)->raw;
    }

    public function cancelTransaction(string $orderId, int $amount): array
    {
        $sale = new Sale([
            'invoice_number' => $orderId,
            'total' => $amount,
        ]);

        $this->gateway->cancelTransaction($sale);

        return ['cancelled' => true];
    }
}
