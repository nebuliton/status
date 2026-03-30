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

    'discord' => [
        'status_webhook_url' => env('DISCORD_STATUS_WEBHOOK_URL'),
    ],

    'nebuliton' => [
        'logo_url' => env('NEBULITON_LOGO_URL', 'https://nebuliton.io/logo.png'),
        'shop_url' => env('NEBULITON_SHOP_URL', 'https://cp.nebuliton.io'),
        'control_panel_url' => env('NEBULITON_CONTROL_PANEL_URL', '/admin'),
        'github_url' => env('NEBULITON_GITHUB_URL', 'https://github.com/nebuliton/status'),
    ],

];
