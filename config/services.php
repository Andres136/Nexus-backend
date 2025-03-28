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
    'siigo' => [
        'base_url' => env('SIIGO_API_URL'),
        'username' => env('SIIGO_USERNAME'),
        'access_key' => env('SIIGO_ACCESS_KEY'),
        'partner_id' => env('SIIGO_PARTNER_ID'),
        
    ],
    'siigo2' => [
    'api_url'    => env('SIIGO2_API_URL', 'https://api.siigo.com'),
    'username'   => env('SIIGO2_USERNAME'),
    'access_key' => env('SIIGO2_ACCESS_KEY'),
    'partner_id' => env('SIIGO2_PARTNER_ID'),
],



];
