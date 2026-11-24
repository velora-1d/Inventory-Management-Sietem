<?php

namespace App\Payments\Drivers;

/**
 * VeraPay Payment Hub driver.
 *
 * Uses the same checkout, status, cancel, and webhook protocol as Pakasir
 * while reading VeraPay credentials from Settings or config/payment.php.
 */
class VeraPayGateway extends PakasirGateway
{
    public function key(): string
    {
        return 'verapay';
    }

    public function label(): string
    {
        return (string) config('payment.gateways.verapay.label', 'VeraPay Payment Hub');
    }

    protected function displayName(): string
    {
        return 'VeraPay';
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $base = config('payment.gateways.verapay', config('services.verapay', []));

        return [
            'api_key' => $this->settingOrFallback('payment_verapay_api_key', $base['api_key'] ?? config('services.verapay.api_key')),
            'slug' => $this->settingOrFallback('payment_verapay_slug', $base['slug'] ?? config('services.verapay.slug')),
            'base_url' => $this->settingOrFallback('payment_verapay_base_url', $base['base_url'] ?? config('services.verapay.base_url', 'https://verapay.digital')),
        ];
    }
}
