<?php

namespace App\Payments\DTOs;

readonly class PaymentWebhookPayload
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $gateway,
        public string $orderId,
        public string $reference,
        public string $status,
        public int $amount,
        public ?string $paymentMethod = null,
        public bool $verified = false,
        public array $raw = [],
    ) {}
}
