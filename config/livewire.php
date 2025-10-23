<?php

return [
    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'layouts.app',

    'temporary_file_upload' => [
        'disk' => null,        // Example: 'local', 's3'              | Default: 'default'
        'rules' => ['required', 'file', 'max:122880'], // 120MB in KB
        'directory' => null,   // Example: 'tmp'                      | Default: 'livewire-tmp'
        'middleware' => null,  // Example: 'throttle:5,1'            | Default: 'throttle:60,1'
        'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs.
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5, // Max duration (in minutes) before an upload gets invalidated.
    ],

    'manifest_path' => null,

    'back_button_cache' => false,

    'render_on_redirect' => false,
];
