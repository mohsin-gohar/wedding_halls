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
    | a conventional file to locate their credentials.
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
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API (Meta)
    |--------------------------------------------------------------------------
    |
    | Credentials for the two-way WhatsApp RSVP webhook and outbound dispatch.
    | When access_token + phone_number_id are absent the app falls back to
    | logging outbound messages (sandbox mode).
    |
    */

    'whatsapp' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v26.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailgun
    |--------------------------------------------------------------------------
    |
    | Mailgun is used for transactional email (booking confirmations, payment
    | receipts, password resets). Set MAIL_MAILER=mailgun and these values.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    |
    | All bookings are cash-only. Payment records are created manually by
    | authorized staff after physical verification; no gateway credentials
    | or callback URLs are supported.
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway
    |--------------------------------------------------------------------------
    |
    | Supported drivers:
    |   log  - record the message locally only (never marks it as delivered)
    |   http - POST to a generic HTTP gateway using HTTP Basic credentials
    |   null - disable outbound SMS entirely
    |
    | `sms_sent_at` is only written after the gateway accepts the request with
    | a 2xx response. A missing endpoint/credential is fail-closed.
    |
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'endpoint' => env('SMS_GATEWAY_URL'),
        'username' => env('SMS_GATEWAY_USERNAME'),
        'password' => env('SMS_GATEWAY_PASSWORD'),
        'sender' => env('SMS_GATEWAY_SENDER_ID', 'RiwajGrand'),
        'timeout' => (int) env('SMS_TIMEOUT', 10),
        'allow_insecure' => (bool) env('SMS_ALLOW_INSECURE', false),
    ],

];
