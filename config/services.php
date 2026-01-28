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

    'whatsapp' => [
        // MaxChat API endpoints
        'api_url_reply' => env('MAXCHAT_API_URL_REPLY'),
        'api_url_push' => env('MAXCHAT_API_URL_PUSH'),
        'api_token' => env('MAXCHAT_API_TOKEN'),
        
        // Template IDs (approved by Meta)
        'checkin_template_id' => env('MAXCHAT_CHECKIN_TEMPLATE_ID'),
        'reward_template_id' => env('MAXCHAT_REWARD_TEMPLATE_ID'),
    ],

];
