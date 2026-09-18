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
        'base_url' => env('WAHA_BASE_URL', 'http://waha:3000'),
        'session'  => env('WAHA_SESSION', 'default'),
        'api_key'  => env('WAHA_API_KEY'),
    ],
    'kref' => [
        'admin_phone'      => env('KREF_ADMIN_PHONE', '6283862681541'),
        'ops_group_id'     => env('KREF_OPS_GROUP_ID', '120363423614283565@g.us'),
        'notify_ops_group' => env('KREF_NOTIFY_OPS_GROUP', true),
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
        'supported_refund_issuers' => [
            // --- Non-Banks (e-Wallets) ---
            'ASTRAPAY', 'LINKAJA', 'GOPAY', 'KASPRO', 'OVO', 'SHOPEEPAY', 'AIRPAY', 'BIMASAKTI', 'DANA', 'VIRGO', 
            'GUDANG VOUCHER', 'BLUEPAY', 'PAYTREN', 'OTTOCASH', 'TMONEY', 'ISAKU', 'DIPAY', 'SALDOMU', 'PAC CASH', 'GDC PAY', 
            'DUTAMONEY', 'WHIZ', 'EZEELINK', 'FINPAY', 'QOIN', 'NUSAPAY', 'PAKAI DONK', 'YOURPAY', 'JAWARA MOBILE', 'YOOPAY', 'SINGAPAY',

            // --- Banking Institutions ---
            'BRI', 'DANAMON', 'PERMATA', 'BCA', 'CENTRAL ASIA', 'BCA DIGITAL', 'BCA SYARIAH', 'PAPUA', 'MAYBANK', 'NEO COMMERCE', 'CIMB', 
            'JATENG', 'SEABANK', 'JAGO', 'BJB', 'ALADIN', 'BPD JATIM', 'MANDIRI TASPEN', 'SMBC', 'JENIUS', 'BENGKULU', 'SULUTGO', 'KROM', 
            'SINARMAS', 'BPD DIY', 'YOGYAKARTA', 'UOB', 'SUPERBANK',
        ],
    ],
];
