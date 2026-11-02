<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daraja (Safaricom M-Pesa) platform configuration
    |--------------------------------------------------------------------------
    |
    | Platform-level credentials used for subscription STK pushes. Env names
    | mirror Shira's (farmcore config/settings/base.py) so one credential
    | set can be dropped into both products in the same sitting.
    |
    | Until these are populated the payment path fails closed: initiation
    | returns a "payment service unavailable" message and nothing is billed.
    */

    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),

    'shortcode' => env('MPESA_SHORTCODE', ''),

    'passkey' => env('MPESA_PASSKEY', ''),

    'api_url' => env('MPESA_API_URL', 'https://sandbox.safaricom.co.ke'),

    'subscription_callback_url' => env('MPESA_SUBSCRIPTION_CALLBACK_URL', ''),

];
