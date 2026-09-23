<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $appName = trim((string) config('seo.site_name', 'Tá na Vitrine')) ?: 'Tá na Vitrine';
        $appUrl = rtrim((string) config('seo.site_url', config('app.url', url('/'))), '/');
        $defaultDescription = (string) config('seo.default_description');
        $defaultOgImage = $appUrl . '/' . ltrim((string) config('seo.default_image', '/images/og.png'), '/');
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
        $seoKeywords = trim((string) ($seo['keywords'] ?? ''));
        $seoRobots = trim((string) ($seo['robots'] ?? '')) ?: 'noindex, nofollow';
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
        $twitterSite = trim((string) ($seo['twitterSite'] ?? '')) ?: (string) config('seo.twitter_site', '@tanavitrine');
        $themeColor = trim((string) ($seo['themeColor'] ?? '')) ?: (string) config('seo.theme_color', '#0f766e');
        $googleAdsTagId = trim((string) (config('services.google_ads.tag_id') ?: 'AW-17767970269'));

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

        $structuredData = $pageProps['structuredData'] ?? null;
        if (is_array($structuredData) && $structuredData !== []) {
            if (array_is_list($structuredData)) {
                foreach ($structuredData as $structuredDataItem) {
                    if (is_array($structuredDataItem) && $structuredDataItem !== []) {
                        $schema['@graph'][] = $structuredDataItem;
                    }
                }
            } else {
                $schema['@graph'][] = $structuredData;
            }
        }
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if ($seoKeywords !== '')
        <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="theme-color" content="{{ $themeColor }}">
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
    <meta name="twitter:site" content="{{ $twitterSite }}">

    <!-- Structured Data -->
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

    @if ($googleAdsTagId !== '')
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($googleAdsTagId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', '{{ $googleAdsTagId }}');
        </script>
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

</body>

</html>
