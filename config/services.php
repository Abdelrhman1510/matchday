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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        // All OAuth client IDs (web, iOS, Android) whose ID tokens we accept.
        // Comma-separated in GOOGLE_CLIENT_IDS; falls back to GOOGLE_CLIENT_ID.
        'client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GOOGLE_CLIENT_IDS', (string) env('GOOGLE_CLIENT_ID')))
        ))),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'brevo' => [
        // Brevo (Sendinblue) REST API v3 key — used by the 'brevo' mail transport
        // to send email over HTTPS (port 443) where outbound SMTP is blocked.
        'key' => env('BREVO_API_KEY'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        // Every audience whose Apple identity token we accept: the iOS app's
        // bundle ID (native "Sign in with Apple") and any web Services ID.
        // Comma-separated in APPLE_CLIENT_IDS; falls back to APPLE_CLIENT_ID.
        'client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('APPLE_CLIENT_IDS', (string) env('APPLE_CLIENT_ID')))
        ))),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'moyasar' => [
        // Secret key (sk_test_… / sk_live_…) — server only, never sent to the app.
        'secret_key' => env('MOYASAR_SECRET_KEY'),
        // Shared secret sent by Moyasar on webhook calls, for verification.
        'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),
        'base_url' => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
    ],

];
