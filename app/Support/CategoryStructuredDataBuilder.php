<?php

declare(strict_types=1);

namespace App\Support;

final class CategoryStructuredDataBuilder
{
    /**
     * @param  list<array{name: string, url: string}>  $breadcrumbs
     * @param  list<array{name: string, url: string}>  $items
     * @return list<array<string, mixed>>
     */
    public function build(
        string $canonical,
        string $title,
        string $description,
        array $breadcrumbs,
        array $items,
        int $positionOffset = 0,
    ): array {
        $canonical = $this->absoluteUrl($canonical);
        $breadcrumbId = $canonical.'#breadcrumb';
        $itemListId = $canonical.'#items';

        $breadcrumbItems = [];
        foreach ($breadcrumbs as $breadcrumb) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name' => $breadcrumb['name'],
                'item' => $this->absoluteUrl($breadcrumb['url']),
            ];
        }

        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $positionOffset + $index + 1,
                'name' => $item['name'],
                'url' => $this->absoluteUrl($item['url']),
            ];
        }

        return [
            array_filter([
                '@type' => 'CollectionPage',
                '@id' => $canonical.'#webpage',
                'url' => $canonical,
                'name' => $title,
                'description' => $description,
                'breadcrumb' => $breadcrumbItems !== [] ? ['@id' => $breadcrumbId] : null,
                'mainEntity' => $listItems !== [] ? ['@id' => $itemListId] : null,
                'inLanguage' => 'pt-BR',
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbId,
                'itemListElement' => $breadcrumbItems,
            ],
            [
                '@type' => 'ItemList',
                '@id' => $itemListId,
                'numberOfItems' => count($listItems),
                'itemListElement' => $listItems,
            ],
        ];
    }

    private function absoluteUrl(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $configured = config('seo.site_url', 'https://tanavitrine.com.br');
        $baseUrl = rtrim(is_string($configured) ? $configured : 'https://tanavitrine.com.br', '/');

        return $baseUrl.'/'.ltrim($value, '/');
    }
}
