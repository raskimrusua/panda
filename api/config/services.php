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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'kindwise' => [
        'key' => env('KINDWISE_API_KEY'),
        'url' => env('KINDWISE_API_URL', 'https://crop.kindwise.com/api/v1'),
        'timeout' => env('KINDWISE_TIMEOUT', 20),
    ],

    /*
    | Disease detection provider toggle. `mock` (default) uses the
    | deterministic MockCropHealthClient — zero cost, no network. Flip
    | to `kindwise` in prod once KINDWISE_API_KEY is set.
    */
    'crop_health' => [
        'provider' => env('CROP_HEALTH_PROVIDER', 'mock'),
    ],

];
