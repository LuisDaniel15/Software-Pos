<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Factus API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Factus API
    |
    */

    'environment' => env('FACTUS_ENVIRONMENT', 'sandbox'),

    'credentials' => [
        'client_id' => env('FACTUS_CLIENT_ID'),
        'client_secret' => env('FACTUS_CLIENT_SECRET'),
        'username' => env('FACTUS_USERNAME'),
        'password' => env('FACTUS_PASSWORD'),
    ],

    'urls' => [
        'sandbox' => env('FACTUS_SANDBOX_URL', 'https://api-sandbox.factus.com.co'),
        'production' => env('FACTUS_PRODUCTION_URL', 'https://api.factus.com.co'),
    ],

    'token' => [
        'cache_key' => 'factus_access_token',
        'refresh_cache_key' => 'factus_refresh_token',
        'expires_cache_key' => 'factus_token_expires_at',
        'ttl' => 3600, // 1 hora en segundos
        'refresh_before' => 300, // Renovar 5 minutos antes de expirar
    ],

    'timeout' => 30, // Timeout de requests en segundos

    'retry' => [
        'times' => 3,
        'sleep' => 1000, // milisegundos
    ],
];