<?php

namespace App\Payments\DTOs;

readonly class PaymentStatusResult
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $gateway,
        public string $reference,
        public string $status,
        public int $amount = 0,
        public ?string $paymentMethod = null,
        public array $raw = [],
    ) {}
}
