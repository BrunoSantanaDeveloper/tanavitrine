<?php

return [
    'name' => 'DigitalSignage',

    // Configurações de armazenamento
    'storage' => [
        'disk' => env('DIGITAL_SIGNAGE_DISK', 'public'),
        'path' => env('DIGITAL_SIGNAGE_PATH', 'digital-signage'),
    ],

    // Configurações de cache
    'cache' => [
        'ttl' => env('DIGITAL_SIGNAGE_CACHE_TTL', 3600),
    ],

    // Configurações de player
    'player' => [
        'refresh_interval' => env('DIGITAL_SIGNAGE_REFRESH_INTERVAL', 60),
        'token_lifetime' => env('DIGITAL_SIGNAGE_TOKEN_LIFETIME', 1440), // 24 horas
    ],
];
