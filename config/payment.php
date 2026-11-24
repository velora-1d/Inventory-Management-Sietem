<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "manual", "pakasir", "midtrans", "xendit", "custom"
    | Can be overridden at runtime via the "payment_gateway" database setting.
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Payment Methods Handled by Online Gateway
    |--------------------------------------------------------------------------
    |
    | When an external gateway is active (anything other than "manual"), these
    | payment methods initiate an online checkout URL and stay PENDING until
    | confirmed via webhook or manual completion.
    |
    */
    'gateway_methods' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PAYMENT_GATEWAY_METHODS', 'qris,ewallet,virtual_account'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Configured Payment Gateways
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'manual' => [
            'driver' => 'manual',
            'label' => 'Manual / Offline (Cash, Transfer, QRIS Statis)',
        ],

        'pakasir' => [
            'driver' => 'pakasir',
            'label' => 'Pakasir Payment Gateway',
            'api_key' => env('PAKASIR_API_KEY'),
            'api_secret' => env('PAKASIR_API_SECRET'),
            'base_url' => env('PAKASIR_BASE_URL', 'https://app.pakasir.com'),
            'slug' => env('PAKASIR_SLUG'),
            'callback_url' => env('PAKASIR_CALLBACK_URL'),
        ],

        'midtrans' => [
            'driver' => 'midtrans',
            'label' => 'Midtrans Snap',
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
            'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
            'snap_sandbox_url' => 'https://app.sandbox.midtrans.com',
            'snap_production_url' => 'https://app.midtrans.com',
            'api_sandbox_url' => 'https://api.sandbox.midtrans.com',
            'api_production_url' => 'https://api.midtrans.com',
        ],

        'xendit' => [
            'driver' => 'xendit',
            'label' => 'Xendit Invoice',
            'secret_key' => env('XENDIT_SECRET_KEY'),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
            'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
        ],

        'custom' => [
            'driver' => 'custom',
            'label' => 'Custom / Generic Webhook Gateway',
            'checkout_url_template' => env('PAYMENT_CUSTOM_CHECKOUT_URL'),
            'status_url_template' => env('PAYMENT_CUSTOM_STATUS_URL'),
            'cancel_url_template' => env('PAYMENT_CUSTOM_CANCEL_URL'),
            'api_key' => env('PAYMENT_CUSTOM_API_KEY'),
            'webhook_secret' => env('PAYMENT_CUSTOM_WEBHOOK_SECRET'),
            'header_name' => env('PAYMENT_CUSTOM_HEADER_NAME', 'X-Webhook-Secret'),
        ],
    ],
];
