<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;
use App\Models\Team;

final class StoreBreadcrumbBuilder
{
    /**
     * @return list<array{name: string, url: string, current: bool}>
     */
    public function build(Team $store, string $canonical): array
    {
        $siteUrl = rtrim((string) config('seo.site_url', config('app.url')), '/');
        $breadcrumbs = [[
            'name' => 'Início',
            'url' => $siteUrl,
            'current' => false,
        ]];

        $landingPage = $this->landingPage($store);
        if ($landingPage !== null) {
            $breadcrumbs[] = [
                'name' => $landingPage['name'],
                'url' => $siteUrl.$landingPage['path'],
                'current' => false,
            ];
        }

        $breadcrumbs[] = [
            'name' => $this->clean($store->name) ?? 'Loja',
            'url' => $canonical,
            'current' => true,
        ];

        return $breadcrumbs;
    }

    /** @return array{name: string, path: string}|null */
    private function landingPage(Team $store): ?array
    {
        if ((bool) $store->is_manufacturer) {
            return ['name' => 'Fabricantes', 'path' => '/fabricantes'];
        }

        $saleType = mb_strtolower(trim((string) $store->sale_type));

        return match ($saleType) {
            'atacado', 'ambos' => ['name' => 'Atacado', 'path' => '/atacado'],
            'varejo' => ['name' => 'Varejo', 'path' => '/varejo'],
            default => null,
        };
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
