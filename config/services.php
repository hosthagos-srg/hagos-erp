<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    /*
    | API website e-commerce Hagos (hagosperfume.com) — ERP menarik pesanan (pull).
    | Base URL contoh produksi: https://hagosperfume.com/api
    */
    'hagos_web' => [
        'base_url' => env('HAGOS_WEB_BASE_URL', 'https://hagosperfume.com/api'),
        'login'    => env('HAGOS_WEB_LOGIN'),     // email/username akun service di website
        'password' => env('HAGOS_WEB_PASSWORD'),  // password akun service
        'akun_kas' => env('HAGOS_WEB_AKUN_KAS', 'Midtrans'), // akun kas tujuan uang masuk
    ],

];
