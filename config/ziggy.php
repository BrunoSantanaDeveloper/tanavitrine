<?php

declare(strict_types=1);

return [
    'groups' => [
        'digital-signage' => [
            'digital-signage.*',
            'player.*'
        ]
    ],
    'url' => env('APP_URL', 'http://localhost'),
    'port' => env('APP_PORT', null),
    'domain' => env('APP_DOMAIN', null),
    'scheme' => env('APP_SCHEME', 'http'),
];
