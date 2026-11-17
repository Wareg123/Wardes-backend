<?php

return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY')],
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'base_url' => env('MIDTRANS_BASE_URL', 'https://app.sandbox.midtrans.com'),
    ],
    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
    ],
    'wardes_payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'midtrans'),
        'default_shipping' => env('WARDES_DEFAULT_SHIPPING_COST', 5000),
    ],
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],
];
