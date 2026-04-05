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

    /*
    | 小程序微信登录：须走 config()，勿在业务代码里直接 env()，
    | 否则 `php artisan config:cache` 后 env() 为空，开发兜底与密钥会全部失效。
    */
    'wechat' => [
        'app_id' => env('WECHAT_APP_ID'),
        'app_secret' => env('WECHAT_APP_SECRET'),
        'dev_bypass' => filter_var(env('WECHAT_DEV_BYPASS', false), FILTER_VALIDATE_BOOLEAN),
        'dev_force_user_id' => env('WECHAT_DEV_FORCE_USER_ID'),
    ],

];
