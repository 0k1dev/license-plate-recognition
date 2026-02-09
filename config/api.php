<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your API settings including allowed API keys
    | for client applications.
    |
    */

    'allowed_keys' => [
        // Chung cho tất cả platforms
        env('API_KEY', 'default-' . md5('fallback')),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    */

    'current_version' => 'v1',
    'supported_versions' => ['v1'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting per API Key
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'default' => 60, // requests per minute
        'auth' => 5,     // for login/register endpoints
        'heavy' => 10,   // for resource-intensive operations
    ],
];
