<?php

return [
    'pages' => [
        'auth' => [
            'login' => [
                'title' => 'Login',
                'heading' => 'Entre na sua conta',
                'form' => [
                    'email' => [
                        'label' => 'E-mail',
                    ],
                    'password' => [
                        'label' => 'Senha',
                    ],
                    'remember' => [
                        'label' => 'Lembrar-me',
                    ],
                    'actions' => [
                        'authenticate' => [
                            'label' => 'Entrar',
                        ],
                    ],
                ],
                'actions' => [
                    'register' => [
                        'label' => 'Criar uma conta',
                    ],
                    'request_password_reset' => [
                        'label' => 'Esqueceu sua senha?',
                    ],
                ],
                'messages' => [
                    'failed' => 'Estas credenciais não correspondem aos nossos registros.',
                ],
                'notifications' => [
                    'throttled' => [
                        'title' => 'Muitas tentativas de login. Por favor, tente novamente em :seconds segundos.',
                        'body' => 'Muitas tentativas de login. Por favor, tente novamente em :seconds segundos.',
                    ],
                ],
            ],
        ],
    ],
    'resources' => [
        'pages' => [
            'title' => 'Páginas',
            'label' => 'Página',
            'plural_label' => 'Páginas',
            'navigation_label' => 'Páginas',
            'navigation_group' => 'Conteúdo',
            'navigation_sort' => 1,
            'navigation_icon' => 'heroicon-o-document-text',
            'form' => [
                'title' => [
                    'label' => 'Título',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
                'content' => [
                    'label' => 'Conteúdo',
                ],
                'meta_title' => [
                    'label' => 'Meta Título',
                ],
                'meta_description' => [
                    'label' => 'Meta Descrição',
                ],
                'is_published' => [
                    'label' => 'Publicado',
                ],
                'published_at' => [
                    'label' => 'Data de Publicação',
                ],
            ],
            'table' => [
                'title' => [
                    'label' => 'Título',
                ],
                'slug' => [
                    'label' => 'Slug',
                ],
                'is_published' => [
                    'label' => 'Publicado',
                ],
                'published_at' => [
                    'label' => 'Data de Publicação',
                ],
                'created_at' => [
                    'label' => 'Criado em',
                ],
                'updated_at' => [
                    'label' => 'Atualizado em',
                ],
            ],
        ],
    ],
    'widgets' => [
        'stats_overview' => [
            'title' => 'Visão Geral',
        ],
    ],
    'global_search' => [
        'placeholder' => 'Pesquisar...',
        'no_results' => 'Nenhum resultado encontrado.',
    ],
    'navigation' => [
        'groups' => [
            'content' => [
                'label' => 'Conteúdo',
                'icon' => 'heroicon-o-document-text',
            ],
            'settings' => [
                'label' => 'Configurações',
                'icon' => 'heroicon-o-cog',
            ],
        ],
    ],
];
