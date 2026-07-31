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

    'meta_whatsapp' => [
        'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
        'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('META_WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'graph_api_version' => env('META_GRAPH_API_VERSION'),
        'live_send_enabled' => env('WHATSAPP_LIVE_SEND_ENABLED', false),
        'autoreply_enabled' => env('WHATSAPP_AUTOREPLY_ENABLED', false),
        'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'en_US'),
        'webhook_retention_days' => (int) env('WHATSAPP_WEBHOOK_RETENTION_DAYS', 30),
        'customer_service_window_hours' => (int) env('WHATSAPP_CUSTOMER_SERVICE_WINDOW_HOURS', 24),
        'internal_signature_max_age_seconds' => (int) env('BWA_INTERNAL_SIGNATURE_MAX_AGE_SECONDS', 300),
        'application_event_timeout_seconds' => (int) env('BWA_APPLICATION_EVENT_TIMEOUT_SECONDS', 10),
    ],

];
