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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'vapi' => [
        'api_key' => env('VAPI_API_KEY'),
        'token' => env('VAPI_API_KEY'),
        'base_url' => env('VAPI_BASE_URL', 'https://api.vapi.ai'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'address_sids' => [
            'AU' => env('TWILIO_AUSTRALIA_ADDRESS_SID', 'AD41a1be18b65714198009c9004ed9d2cd'),
            'CA' => env('TWILIO_CANADA_ADDRESS_SID', 'ADefbe5e284152650c6ee9c622378569e6'),
            'GB' => env('TWILIO_UK_ADDRESS_SID', 'AD4c3f06239dbde51132ce10dc187822e6'),
            'MX' => env('TWILIO_MEXICO_ADDRESS_SID', 'AD30473246927465ab0708ee7c14eef6ed'),
        ],
        'bundle_sids' => [
            'GB' => env('TWILIO_UK_BUNDLE_SID', 'AD4c3f06239dbde51132ce10dc187822e6'),
            'MX' => env('TWILIO_MEXICO_BUNDLE_SID', 'BUe391fdf5853e3981061a5b30720d1b59'),
            // 'MX' => env('TWILIO_MEXICO_BUNDLE_SID', 'ADc7ce5e8a422016644c9a50dc55ee01f1'),
        ],
    ],

];
