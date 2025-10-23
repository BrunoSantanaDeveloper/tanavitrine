<?php

return [
    'title' => 'Login',
    'heading' => 'Sign in to your account',
    'form' => [
        'email' => [
            'label' => 'Email',
        ],
        'password' => [
            'label' => 'Password',
        ],
        'remember' => [
            'label' => 'Remember me',
        ],
        'actions' => [
            'authenticate' => [
                'label' => 'Sign in',
            ],
        ],
    ],
    'actions' => [
        'register' => [
            'label' => 'Create an account',
        ],
        'request_password_reset' => [
            'label' => 'Forgot your password?',
        ],
    ],
    'messages' => [
        'failed' => 'These credentials do not match our records.',
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Too many login attempts. Please try again in :seconds seconds.',
            'body' => 'Too many login attempts. Please try again in :seconds seconds.',
        ],
    ],
];
