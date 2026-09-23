<?php

declare(strict_types=1);

return [
    'site_name' => env('SEO_SITE_NAME', 'Tá na Vitrine'),
    'site_url' => env('SEO_SITE_URL', env('APP_URL', 'https://tanavitrine.com.br')),
    'indexing_enabled' => (bool) env('SEO_INDEXING_ENABLED', false),

    'default_description' => 'Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil. Encontre lojas de atacado e varejo com contato direto.',
    'default_image' => '/images/og.png',
    'theme_color' => '#0f766e',
    'twitter_site' => '@tanavitrine',

    /*
     * Only these named routes may be indexed when SEO_INDEXING_ENABLED=true.
     * Everything else is noindex by default.
     */
    'indexable_routes' => [
        'home',
        'atacado',
        'varejo',
        'fabricantes',
        'prices',
        'about',
        'policy.show',
        'terms.show',
        'store.show',
    ],

    /*
     * Category pages are intentionally disabled until their frontend pages are
     * implemented. This prevents public routes from returning an HTTP 500.
     */
    'category_pages_enabled' => (bool) env('SEO_CATEGORY_PAGES_ENABLED', false),

    /*
     * Metadata for framework-owned pages that do not have an application
     * controller where SEO props can be attached directly.
     */
    'route_meta' => [
        'policy.show' => [
            'title' => 'Política de Privacidade',
            'description' => 'Conheça a Política de Privacidade da Tá na Vitrine e como tratamos os dados dos usuários da plataforma.',
            'canonical' => '/privacy-policy',
            'indexable' => true,
        ],
        'terms.show' => [
            'title' => 'Termos de Serviço',
            'description' => 'Leia os Termos de Serviço da Tá na Vitrine e entenda as regras de uso da plataforma para lojistas e compradores.',
            'canonical' => '/terms-of-service',
            'indexable' => true,
        ],
        'login' => [
            'title' => 'Login',
            'canonical' => '/login',
        ],
        'register' => [
            'title' => 'Cadastro',
            'canonical' => '/register',
        ],
        'password.request' => [
            'title' => 'Recuperar senha',
            'canonical' => '/forgot-password',
        ],
        'password.reset' => [
            'title' => 'Redefinir senha',
            'canonical' => '/reset-password',
        ],
        'verification.notice' => [
            'title' => 'Verificar e-mail',
            'canonical' => '/email/verify',
        ],
        'password.confirm' => [
            'title' => 'Confirmar senha',
            'canonical' => '/user/confirm-password',
        ],
        'two-factor.login' => [
            'title' => 'Autenticação de dois fatores',
            'canonical' => '/two-factor-challenge',
        ],
    ],
];
