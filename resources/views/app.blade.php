<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $appName = 'Tá na Vitrine';
        $appUrl = rtrim(config('app.url', url('/')), '/');
        $defaultDescription = 'Tá na Vitrine conecta fornecedores e lojistas de moda em todo o Brasil. Encontre lojas de atacado e varejo com contato direto.';
        $defaultKeywords = 'tá na vitrine, tanavitrine, tana vitrine, ta na vitrine, atacado de moda, varejo de moda, fornecedores de moda';
        $defaultOgImage = $appUrl . '/images/og.png';
        $pageProps = is_array($page['props'] ?? null) ? $page['props'] : [];
        $seo = is_array($pageProps['seo'] ?? null) ? $pageProps['seo'] : [];

        $toAbsoluteUrl = static function (?string $value) use ($appUrl): ?string {
            $normalized = trim((string) $value);
            if ($normalized === '') {
                return null;
            }

            if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
                return $normalized;
            }

            return $appUrl . '/' . ltrim($normalized, '/');
        };

        $seoTitle = trim((string) ($seo['title'] ?? '')) ?: $appName;
        $seoDescription = trim((string) ($seo['description'] ?? '')) ?: $defaultDescription;
        $seoKeywords = trim((string) ($seo['keywords'] ?? '')) ?: $defaultKeywords;
        $seoRobots = trim((string) ($seo['robots'] ?? '')) ?: 'index, follow';
        $canonicalUrl = $toAbsoluteUrl($seo['canonical'] ?? null)
            ?? $toAbsoluteUrl($seo['ogUrl'] ?? null)
            ?? url()->current();
        $ogType = trim((string) ($seo['ogType'] ?? '')) ?: 'website';
        $ogTitle = trim((string) ($seo['ogTitle'] ?? '')) ?: $seoTitle;
        $ogDescription = trim((string) ($seo['ogDescription'] ?? '')) ?: $seoDescription;
        $ogUrl = $toAbsoluteUrl($seo['ogUrl'] ?? null) ?? $canonicalUrl;
        $ogImage = $toAbsoluteUrl($seo['ogImage'] ?? null) ?? $defaultOgImage;
        $twitterCard = trim((string) ($seo['twitterCard'] ?? '')) ?: 'summary_large_image';
        $twitterTitle = trim((string) ($seo['twitterTitle'] ?? '')) ?: $ogTitle;
        $twitterDescription = trim((string) ($seo['twitterDescription'] ?? '')) ?: $ogDescription;
        $twitterImage = $toAbsoluteUrl($seo['twitterImage'] ?? null) ?? $ogImage;

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $appUrl . '#organization',
                    'name' => $appName,
                    'alternateName' => ['Tanavitrine', 'Tana Vitrine', 'Ta na Vitrine'],
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
                    'alternateName' => ['Tanavitrine', 'Tana Vitrine', 'Ta na Vitrine'],
                    'publisher' => [
                        '@id' => $appUrl . '#organization',
                    ],
                    'inLanguage' => 'pt-BR',
                ],
            ],
        ];

        $store = is_array($pageProps['store'] ?? null) ? $pageProps['store'] : null;
        if ($store) {
            $addressLine = trim(implode(', ', array_filter([
                $store['address'] ?? null,
                $store['address_number'] ?? null,
            ])));

            $storeSchema = array_filter([
                '@type' => 'Store',
                '@id' => $ogUrl . '#store',
                'name' => $store['name'] ?? null,
                'description' => $seoDescription,
                'url' => $ogUrl,
                'image' => $ogImage,
                'telephone' => $store['phone'] ?? null,
                'email' => $store['email'] ?? null,
                'sameAs' => array_values(array_filter([
                    $store['website'] ?? null,
                    $store['instagram'] ?? null,
                    $store['facebook'] ?? null,
                    $store['tiktok'] ?? null,
                ])),
            ], fn ($value) => $value !== null && $value !== '' && $value !== []);

            $postalAddress = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $addressLine ?: null,
                'addressLocality' => $store['city'] ?? null,
                'addressRegion' => $store['state'] ?? null,
                'postalCode' => $store['zip_code'] ?? null,
                'addressCountry' => 'BR',
            ], fn ($value) => $value !== null && $value !== '');

            if (count($postalAddress) > 2) {
                $storeSchema['address'] = $postalAddress;
            }

            $schema['@graph'][] = $storeSchema;
        }
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <!-- Favicon & App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Social tags -->
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    <meta name="twitter:image" content="{{ $twitterImage }}">

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
