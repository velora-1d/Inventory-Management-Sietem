<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'store_name' => 'TB. Kencana Pamijahan',
            'store_address' => 'Pamijahan, Kec. Plumbon, Kabupaten Cirebon, Jawa Barat 45155',
            'store_phone' => '081234567890',
            'opening_balance_date' => now()->startOfYear()->toDateString(),
            'opening_balance_amount' => '10000000',
            'currency_symbol' => 'Rp',
            'currency_position' => 'left',
            'currency_fraction_digits' => '0',
            'currency_thousand_separator' => '.',
            'currency_decimal_separator' => ',',

            // Flexible Branding Configuration
            'brand_name' => 'POS Skillage Edupark',
            'brand_subtitle' => 'Business Operations Platform',
            'brand_short_code' => 'PSE',
            'brand_logo' => '',
            'brand_whatsapp_admin' => '081234567890',

            // Flexible Payment Gateway Configuration
            'payment_gateway' => 'manual',
            'payment_gateway_methods' => 'qris,ewallet,virtual_account',
            'payment_pakasir_base_url' => 'https://app.pakasir.com',
            'payment_pakasir_slug' => '',
            'payment_pakasir_api_key' => '',
            'payment_midtrans_server_key' => '',
            'payment_midtrans_client_key' => '',
            'payment_midtrans_is_production' => 'false',
            'payment_xendit_secret_key' => '',
            'payment_xendit_callback_token' => '',
            'payment_custom_checkout_url' => '',
            'payment_custom_status_url' => '',
            'payment_custom_api_key' => '',
            'payment_custom_webhook_secret' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
