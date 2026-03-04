<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tá na Vitrine') }}</title>
    <meta name="description" content="Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil. Encontre lojas de atacado e varejo com contato direto.">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon & App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    @php
        $appName = config('app.name', 'Tá na Vitrine');
        $appUrl = rtrim(config('app.url', url('/')), '/');
        $defaultOgImage = $appUrl . '/images/og.webp';
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $appUrl . '#organization',
                    'name' => $appName,
                    'url' => $appUrl,
                    'logo' => $appUrl . '/android-chrome-512x512.png',
                    'sameAs' => [
                        'https://instagram.com/tanavitrine',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $appUrl . '#website',
                    'url' => $appUrl,
                    'name' => $appName,
                    'publisher' => [
                        '@id' => $appUrl . '#organization',
                    ],
                    'inLanguage' => 'pt-BR',
                ],
            ],
        ];
    @endphp

    <!-- Fallback social tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $appName }}">
    <meta property="og:description" content="Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $defaultOgImage }}">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $appName }}">
    <meta name="twitter:description" content="Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil.">
    <meta name="twitter:image" content="{{ $defaultOgImage }}">

    <!-- Structured Data -->
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

</body>

</html>
