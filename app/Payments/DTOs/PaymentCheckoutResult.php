<?php

namespace App\Payments\DTOs;

readonly class PaymentCheckoutResult
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $gateway,
        public string $reference,
        public ?string $checkoutUrl = null,
        public string $status = 'pending',
        public bool $requiresRedirect = false,
        public array $meta = [],
    ) {}
}
