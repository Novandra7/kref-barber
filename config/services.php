<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'waha' => [
        'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
        'session'  => env('WAHA_SESSION', 'default'),
        'api_key'  => env('WAHA_API_KEY'),
    ],
    'doku' => [
        'base_url' => env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
        'client_id' => env('DOKU_CLIENT_ID'),
        'secret_key' => env('DOKU_SECRET_KEY'),
        'private_key' => storage_path('app/private/private.key'),
        'merchant_id' => env('DOKU_MERCHANT_ID'),
        'terminal_id' => env('DOKU_TERMINAL_ID'),
        'webhook_secret' => env('DOKU_WEBHOOK_SECRET'),
        'postal_code' => env('DOKU_POSTAL_CODE', '75121'),
    ],
];
