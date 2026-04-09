<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Provider (Module 10 - Notification Delivery)
    | Provider options: 'twilio' | 'log'
    | Use 'log' in development/testing to avoid real SMS charges.
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),
        'from'     => env('SMS_FROM_NUMBER'),
        'twilio'   => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
        ],
    ],

];
