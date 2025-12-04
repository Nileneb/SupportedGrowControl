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
    |--------------------------------------------------------------------------
    | Growdash Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Growdash device integration. Defines webhook security
    | token, default device slug, and Python backend base URL for API calls.
    |
    */

    'growdash' => [
        'webhook_token' => env('GROWDASH_WEBHOOK_TOKEN'),
        'device_slug' => env('GROWDASH_DEVICE_SLUG', 'growdash-1'),
        'python_base_url' => env('GROWDASH_PYTHON_BASE_URL', 'http://localhost:8000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI API (AI/LLM Integration)
    |--------------------------------------------------------------------------
    |
    | OpenAI API für LLM-gestützte Features wie Arduino Error Analysis.
    | API Key: https://platform.openai.com/api-keys
    | Models: gpt-4o-mini (empfohlen), gpt-4o, gpt-3.5-turbo
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
    ],

];
