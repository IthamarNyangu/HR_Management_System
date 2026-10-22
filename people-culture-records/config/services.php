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

    'workpulse' => [
        'enabled' => (bool) env('WORKPULSE_SYNC_ENABLED', false),
        'base_url' => env('WORKPULSE_BASE_URL', 'http://localhost:3000'),
        'sync_token' => env('WORKPULSE_SYNC_TOKEN'),
    ],

    'exitclear' => [
        'enabled' => (bool) env('EXITCLEAR_INTEGRATION_ENABLED', true),
        'base_url' => env('EXITCLEAR_BASE_URL', 'http://127.0.0.1:8012'),
        'integration_token' => env('EXITCLEAR_INTEGRATION_TOKEN', env('WORKPULSE_SYNC_TOKEN')),
        'initiation_path' => env('EXITCLEAR_INITIATION_PATH', '/api/integrations/hrms/exit-cases'),
        'timeout' => (int) env('EXITCLEAR_TIMEOUT', 10),
    ],

];
