<?php

return [
    'pages' => [
        'auth' => [
            'login' => [
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
            ],
        ],
    ],
    'resources' => [
        'pages' => [
            'title' => 'Pages',
            'label' => 'Page',
            'plural_label' => 'Pages',
            'navigation_label' => 'Pages',
            'navigation_group' => 'Content',
            'navigation_sort' => 1,
            'navigation_icon' => 'heroicon-o-document-text',
            'form' => [
                'title' => [
                    'label' => 'Title',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
                'content' => [
                    'label' => 'Content',
                ],
                'meta_title' => [
                    'label' => 'Meta Title',
                ],
                'meta_description' => [
                    'label' => 'Meta Description',
                ],
                'is_published' => [
                    'label' => 'Published',
                ],
                'published_at' => [
                    'label' => 'Published At',
                ],
            ],
            'table' => [
                'title' => [
                    'label' => 'Title',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
                'is_published' => [
                    'label' => 'Published',
                ],
                'published_at' => [
                    'label' => 'Published At',
                ],
                'created_at' => [
                    'label' => 'Created At',
                ],
                'updated_at' => [
                    'label' => 'Updated At',
                ],
            ],
        ],
    ],
    'widgets' => [
        'stats_overview' => [
            'title' => 'Overview',
        ],
    ],
    'global_search' => [
        'placeholder' => 'Search...',
        'no_results' => 'No results found.',
    ],
    'navigation' => [
        'groups' => [
            'content' => [
                'label' => 'Content',
                'icon' => 'heroicon-o-document-text',
            ],
            'settings' => [
                'label' => 'Settings',
                'icon' => 'heroicon-o-cog',
            ],
        ],
    ],
];
