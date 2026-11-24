<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (!function_exists('brand_setting')) {
    function brand_setting(string $key, ?string $default = null): ?string
    {
        try {
            if (Schema::hasTable('settings')) {
                $value = Setting::get($key);
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable) {
            // Fallback gracefully when DB/settings table is unavailable
        }

        return $default;
    }
}

if (!function_exists('brand_name')) {
    function brand_name(): string
    {
        return (string) brand_setting('brand_name', config('app.name', 'POS Skillage Edupark'));
    }
}

if (!function_exists('brand_subtitle')) {
    function brand_subtitle(): string
    {
        return (string) brand_setting('brand_subtitle', 'Business Operations Platform');
    }
}

if (!function_exists('brand_short_code')) {
    function brand_short_code(): string
    {
        return (string) brand_setting('brand_short_code', 'PSE');
    }
}

if (!function_exists('brand_logo_url')) {
    function brand_logo_url(): ?string
    {
        $logo = brand_setting('brand_logo');

        if (!$logo) {
            return null;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '/')) {
            return $logo;
        }

        return Storage::disk('public')->url($logo);
    }
}

if (!function_exists('brand_whatsapp_admin')) {
    function brand_whatsapp_admin(): string
    {
        return (string) brand_setting('brand_whatsapp_admin', brand_setting('store_phone', '081234567890'));
    }
}

if (!function_exists('brand_whatsapp_url')) {
    function brand_whatsapp_url(string $message = ''): ?string
    {
        $rawPhone = preg_replace('/\D+/', '', brand_whatsapp_admin()) ?? '';
        if ($rawPhone === '') {
            return null;
        }

        if (str_starts_with($rawPhone, '0')) {
            $rawPhone = '62' . substr($rawPhone, 1);
        }

        return $message !== ''
            ? "https://wa.me/{$rawPhone}?text=" . urlencode($message)
            : "https://wa.me/{$rawPhone}";
    }
}

if (!function_exists('payment_bank_info')) {
    /**
     * @return array{bank_name: string, account_number: string, account_holder: string}
     */
    function payment_bank_info(): array
    {
        return [
            'bank_name' => trim((string) brand_setting('payment_bank_name', '')),
            'account_number' => trim((string) brand_setting('payment_bank_account_number', '')),
            'account_holder' => trim((string) brand_setting('payment_bank_account_holder', '')),
        ];
    }
}
