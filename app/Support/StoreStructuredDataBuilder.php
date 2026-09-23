<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;
use App\Models\Team;

final class StoreStructuredDataBuilder
{
    /**
     * @param  list<string>  $sameAs
     * @param  list<array{name: string, url: string, current?: bool}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function build(
        Team $store,
        string $canonical,
        string $title,
        string $description,
        string $image,
        ?string $logo = null,
        array $sameAs = [],
        array $breadcrumbs = [],
    ): array {
        $canonical = $this->absoluteUrl($canonical) ?? $canonical;
        $siteUrl = $this->siteUrl($canonical);
        $storeId = $canonical.'#store';
        $webPageId = $canonical.'#webpage';
        $breadcrumbId = $canonical.'#breadcrumb';
        $storeType = $this->storeType($store);

        $storeSchema = array_filter([
            '@type' => $storeType,
            '@id' => $storeId,
            'name' => $this->clean($store->name),
            'description' => $this->clean($description),
            'url' => $canonical,
            'image' => $this->absoluteUrl($image),
            'logo' => $this->absoluteUrl($logo),
            'identifier' => $this->identifier($store),
            'sameAs' => $this->validUrls($sameAs),
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
        ], $this->hasValue(...));

        if ($storeType === 'Store') {
            $storeSchema['address'] = $this->address($store);

            $geo = $this->geo($store);
            if ($geo !== null) {
                $storeSchema['geo'] = $geo;
            }

            $mapUrl = $this->absoluteUrl($store->google_maps_url);
            if ($mapUrl !== null) {
                $storeSchema['hasMap'] = $mapUrl;
            }
        }

        $breadcrumbItems = $this->breadcrumbItems($breadcrumbs);
        $webPageSchema = array_filter([
            '@type' => 'WebPage',
            '@id' => $webPageId,
            'url' => $canonical,
            'name' => $this->clean($title),
            'description' => $this->clean($description),
            'isPartOf' => [
                '@id' => $siteUrl.'#website',
            ],
            'publisher' => [
                '@id' => $siteUrl.'#organization',
            ],
            'mainEntity' => [
                '@id' => $storeId,
            ],
            'breadcrumb' => $breadcrumbItems !== [] ? [
                '@id' => $breadcrumbId,
            ] : null,
            'primaryImageOfPage' => $this->absoluteUrl($image),
            'inLanguage' => 'pt-BR',
        ], $this->hasValue(...));

        $graph = [$webPageSchema];

        if ($breadcrumbItems !== []) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbId,
                'itemListElement' => $breadcrumbItems,
            ];
        }

        $graph[] = $storeSchema;

        return $graph;
    }

    private function storeType(Team $store): string
    {
        $type = mb_strtolower(trim((string) $store->store_type));

        if (in_array($type, ['virtual', 'online'], true)) {
            return 'OnlineStore';
        }

        if (in_array($type, ['fisica', 'física', 'ambos'], true) && $this->hasCompleteAddress($store)) {
            return 'Store';
        }

        return 'Organization';
    }

    private function hasCompleteAddress(Team $store): bool
    {
        return $this->clean($store->address) !== null
            && $this->clean($store->city) !== null
            && $this->clean($store->state) !== null;
    }

    /** @return array<string, string> */
    private function address(Team $store): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim(implode(', ', array_filter([
                $this->clean($store->address),
                $this->clean($store->address_number),
            ]))),
            'addressLocality' => $this->clean($store->city),
            'addressRegion' => $this->clean($store->state),
            'postalCode' => $this->clean($store->zip_code),
            'addressCountry' => 'BR',
        ], $this->hasValue(...));
    }

    /** @return array<string, float|string>|null */
    private function geo(Team $store): ?array
    {
        if (! is_numeric($store->latitude) || ! is_numeric($store->longitude)) {
            return null;
        }

        $latitude = (float) $store->latitude;
        $longitude = (float) $store->longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * @param  list<array{name: string, url: string, current?: bool}>  $breadcrumbs
     * @return list<array<string, int|string>>
     */
    private function breadcrumbItems(array $breadcrumbs): array
    {
        $items = [];

        foreach ($breadcrumbs as $breadcrumb) {
            $name = $this->clean($breadcrumb['name'] ?? null);
            $url = $this->absoluteUrl($breadcrumb['url'] ?? null);

            if ($name === null || $url === null) {
                continue;
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $name,
                'item' => $url,
            ];
        }

        return $items;
    }

    /** @param list<string> $urls */
    private function validUrls(array $urls): array
    {
        $validUrls = array_map($this->absoluteUrl(...), $urls);

        return array_values(array_unique(array_filter($validUrls)));
    }

    private function identifier(Team $store): ?string
    {
        $id = (int) $store->getKey();

        return $id > 0 ? 'TV'.mb_str_pad((string) $id, 4, '0', STR_PAD_LEFT) : null;
    }

    private function siteUrl(string $canonical): string
    {
        $configured = $this->absoluteUrl(config('seo.site_url'));
        if ($configured !== null) {
            return rtrim($configured, '/');
        }

        $parts = parse_url($canonical);
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            $port = isset($parts['port']) ? ':'.$parts['port'] : '';

            return $parts['scheme'].'://'.$parts['host'].$port;
        }

        return 'https://tanavitrine.com.br';
    }

    private function absoluteUrl(mixed $value): ?string
    {
        $url = $this->clean($value);

        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    private function clean(mixed $value): ?string
    {
        $stringValue = (is_scalar($value) || $value instanceof Stringable)
            ? (string) $value
            : '';
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags($stringValue)));

        return $clean !== '' ? $clean : null;
    }
}
