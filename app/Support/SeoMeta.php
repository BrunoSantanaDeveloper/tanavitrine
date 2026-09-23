<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class SeoMeta
{
    /**
     * Build the final SEO payload consumed by Blade and the Vue application.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    public function make(
        string $title,
        ?string $description = null,
        ?string $canonical = null,
        bool $indexable = false,
        array $overrides = [],
    ): array {
        $siteName = $this->siteName();
        $finalTitle = $this->withSiteName($this->cleanText($title), $siteName);
        $finalDescription = $this->cleanText($description ?: (string) config('seo.default_description'));
        $canonicalUrl = $this->canonicalUrl($canonical ?: '/');
        $defaultImage = $this->absoluteUrl((string) config('seo.default_image', '/images/og.png'));
        $canIndex = (bool) config('seo.indexing_enabled', false) && $indexable;

        $seo = [
            'title' => $finalTitle,
            'description' => $finalDescription,
            'robots' => $canIndex ? 'index, follow' : 'noindex, nofollow',
            'canonical' => $canonicalUrl,
            'themeColor' => (string) config('seo.theme_color', '#0f766e'),
            'ogTitle' => $finalTitle,
            'ogDescription' => $finalDescription,
            'ogType' => 'website',
            'ogUrl' => $canonicalUrl,
            'ogImage' => $defaultImage,
            'ogSiteName' => $siteName,
            'ogLocale' => 'pt_BR',
            'twitterTitle' => $finalTitle,
            'twitterDescription' => $finalDescription,
            'twitterCard' => 'summary_large_image',
            'twitterImage' => $defaultImage,
            'twitterSite' => (string) config('seo.twitter_site', '@tanavitrine'),
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            $seo[$key] = is_string($value) ? trim($value) : $value;
        }

        // Indexing cannot be enabled by an override when the environment gate is off.
        $seo['robots'] = $canIndex ? (string) ($overrides['robots'] ?? 'index, follow') : 'noindex, nofollow';
        $seo['canonical'] = $this->canonicalUrl((string) ($seo['canonical'] ?? $canonicalUrl));
        $seo['ogUrl'] = $this->canonicalUrl((string) ($seo['ogUrl'] ?? $seo['canonical']));
        $seo['ogImage'] = $this->absoluteUrl((string) ($seo['ogImage'] ?? $defaultImage));
        $seo['twitterImage'] = $this->absoluteUrl((string) ($seo['twitterImage'] ?? $seo['ogImage']));

        return array_map(
            static fn (mixed $value): string => is_string($value) ? $value : (string) $value,
            $seo,
        );
    }

    /**
     * Provide safe route-aware defaults for pages rendered by framework controllers.
     *
     * @return array<string, string>
     */
    public function forRequest(Request $request): array
    {
        $routeName = $request->route()?->getName();
        $routeMetaByName = (array) config('seo.route_meta', []);
        $routeMeta = is_string($routeName)
            ? ($routeMetaByName[$routeName] ?? [])
            : [];

        if (! is_array($routeMeta)) {
            $routeMeta = [];
        }

        return $this->make(
            title: (string) ($routeMeta['title'] ?? $this->siteName()),
            description: isset($routeMeta['description']) ? (string) $routeMeta['description'] : null,
            canonical: (string) ($routeMeta['canonical'] ?? $request->getPathInfo()),
            indexable: (bool) ($routeMeta['indexable'] ?? false),
        );
    }

    public function isRouteIndexable(Request $request): bool
    {
        if (! (bool) config('seo.indexing_enabled', false)) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return is_string($routeName)
            && in_array($routeName, (array) config('seo.indexable_routes', []), true);
    }

    private function siteName(): string
    {
        return trim((string) config('seo.site_name', 'Tá na Vitrine')) ?: 'Tá na Vitrine';
    }

    private function siteUrl(): string
    {
        $configuredUrl = rtrim(trim((string) config('seo.site_url', '')), '/');

        if (filter_var($configuredUrl, FILTER_VALIDATE_URL)) {
            return $configuredUrl;
        }

        return 'https://tanavitrine.com.br';
    }

    private function withSiteName(string $title, string $siteName): string
    {
        if ($title === '') {
            return $siteName;
        }

        $quotedSiteName = preg_quote($siteName, '/');
        $duplicateSuffix = '/(?:\s*[|\-–—]\s*'.$quotedSiteName.'){2,}\s*$/iu';
        $title = (string) preg_replace($duplicateSuffix, ' | '.$siteName, $title);

        if (mb_stripos($title, $siteName) !== false) {
            return $title;
        }

        return "{$title} | {$siteName}";
    }

    private function cleanText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    private function canonicalUrl(string $value): string
    {
        $value = trim($value);
        $path = parse_url($value, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        $url = $this->siteUrl().'/'.ltrim($path, '/');

        return rtrim($url, '/') ?: $this->siteUrl();
    }

    private function absoluteUrl(string $value): string
    {
        $value = trim($value);

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return $this->siteUrl().'/'.ltrim($value, '/');
    }
}
