<?php

namespace App\Payments\Concerns;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

trait ReadsGatewaySettings
{
    protected function settingOrFallback(string $key, mixed $fallback = null): mixed
    {
        try {
            if (Schema::hasTable('settings')) {
                $value = Setting::get($key);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable) {
            // Fallback to config when DB/settings table is unavailable
        }

        return $fallback;
    }

    protected function settingBoolOrFallback(string $key, bool $fallback = false): bool
    {
        $value = $this->settingOrFallback($key, $fallback);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN);
    }
}
