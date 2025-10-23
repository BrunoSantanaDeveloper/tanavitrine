<?php

return [
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
];
