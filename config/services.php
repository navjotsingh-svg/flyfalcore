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

    'flights' => [
        'provider' => 'tbo',
    ],

    'tbo' => [
        'client_id' => env('TBO_CLIENT_ID'),
        'username' => env('TBO_USERNAME'),
        'password' => env('TBO_PASSWORD'),
        'end_user_ip' => env('TBO_END_USER_IP'),
        'auth_url' => env('TBO_AUTH_URL', 'https://Sharedapi.tektravels.com/SharedData.svc/rest'),
        'air_url' => env('TBO_AIR_URL', 'https://tboapi.travelboutiqueonline.com/AirAPI_V10/AirService.svc/rest'),
        'timeout' => env('TBO_TIMEOUT', 60),
        'currency' => env('TBO_CURRENCY', 'INR'),
    ],

    'fx' => [
        // Used only when PayPal cannot charge the booking currency (e.g. INR → USD).
        'inr_per_usd' => (float) env('FX_INR_PER_USD', 83),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'currency' => env('PAYPAL_CURRENCY', 'INR'),
        'base_url' => env('PAYPAL_BASE_URL', env('PAYPAL_MODE', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com'),
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

];
