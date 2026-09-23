<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;
use App\Models\Team;
use Illuminate\Support\Str;

final class StoreSeoBuilder
{
    public function __construct(private readonly SeoMeta $seoMeta) {}

    /**
     * @return array<string, string>
     */
    public function build(Team $store, string $image): array
    {
        $segment = $this->segment($store);
        $saleIntent = $this->saleIntent($this->stringValue($store->sale_type));
        $location = $this->location($store);
        $titleSaleIntent = $segment === null
            ? $this->saleLabel($this->stringValue($store->sale_type))
            : $saleIntent;

        $title = $this->title((string) $store->name, $segment, $titleSaleIntent, $location);
        $description = $this->description($store, $segment, $saleIntent, $location);

        return $this->seoMeta->make(
            title: $title,
            description: $description,
            canonical: "/loja/{$store->slug}",
            indexable: true,
            overrides: [
                'ogImage' => $image,
                'twitterImage' => $image,
            ],
        );
    }

    public function segment(Team $store): ?string
    {
        $category = $this->clean($store->category?->getAttribute('name'));
        $subcategories = $this->subcategories($store->subcategory);
        $subcategory = $subcategories[0] ?? null;
        $gender = mb_strtolower($this->clean($store->gender) ?? '');
        $categoryKey = mb_strtolower($category ?? '');
        $subcategoryKey = mb_strtolower($subcategory ?? '');

        $fashionLabels = [
            'feminino' => 'Moda Feminina',
            'masculino' => 'Moda Masculina',
            'infantil' => 'Moda Infantil',
            'unissex' => 'Moda Unissex',
            'plus size' => 'Moda Plus Size',
        ];

        if ($categoryKey === 'roupas') {
            if (isset($fashionLabels[$subcategoryKey])) {
                return $fashionLabels[$subcategoryKey];
            }

            if ($subcategoryKey === '' && isset($fashionLabels[$gender])) {
                return $fashionLabels[$gender];
            }
        }

        $audienceLabels = [
            'feminino' => 'Femininos',
            'masculino' => 'Masculinos',
            'infantil' => 'Infantis',
            'unissex' => 'Unissex',
        ];
        if (isset($audienceLabels[$subcategoryKey])) {
            return match ($categoryKey) {
                'calçados', 'calcados' => 'Calçados '.$audienceLabels[$subcategoryKey],
                'acessórios', 'acessorios' => 'Acessórios '.$audienceLabels[$subcategoryKey],
                default => $category,
            };
        }

        if ($subcategoryKey !== '') {
            return match (true) {
                str_starts_with($subcategoryKey, 'moda ') => Str::title((string) $subcategory),
                $subcategoryKey === 'plus size' => 'Moda Plus Size',
                default => $subcategory,
            };
        }

        return $category;
    }

    private function title(string $name, ?string $segment, ?string $saleIntent, ?string $location): string
    {
        $name = $this->clean($name) ?: 'Loja';
        $siteName = $this->stringValue(config('seo.site_name', 'Tá na Vitrine')) ?: 'Tá na Vitrine';
        $name = $this->limitAtWord($name, max(20, 70 - mb_strlen(' | '.$siteName)), '');
        $details = array_values(array_filter([$segment, $saleIntent, $location]));

        while ($details !== []) {
            $candidate = $name.': '.implode(' ', $details);
            if (mb_strlen($candidate.' | '.$siteName) <= 70) {
                return $candidate;
            }

            array_pop($details);
        }

        return $name;
    }

    private function description(Team $store, ?string $segment, ?string $saleIntent, ?string $location): string
    {
        $name = $this->clean($store->name) ?: 'esta loja';
        $business = $segment ? mb_strtolower($segment) : 'moda';
        $saleDescription = $saleIntent ? mb_strtolower($saleIntent) : null;
        $intro = $store->sale_type === 'varejo'
            ? "Conheça {$name}, loja de {$business}".
                ($saleDescription ? ' '.$saleDescription : '').
                ($location ? ' '.$location : '').'.'
            : "Encontre {$name}, fornecedor de {$business}".
                ($saleDescription ? ' '.$saleDescription : '').
                ($location ? ' '.$location : '').'.';

        if (mb_strlen($intro) > 160) {
            return $this->limitAtWord($intro, 160, '...');
        }

        $optionalSentences = [];
        if ((bool) $store->is_manufacturer) {
            $optionalSentences[] = 'Fabricação própria.';
        }

        if (in_array($store->sale_type, ['atacado', 'ambos'], true)) {
            $minimumOrder = StoreMinimumOrder::label($store->min_order);
            if ($minimumOrder !== null) {
                $optionalSentences[] = "Pedido mínimo de {$minimumOrder}.";
            }
        }

        $optionalSentences[] = 'Veja os produtos e fale diretamente com a loja.';
        $description = $intro;
        foreach ($optionalSentences as $sentence) {
            if (mb_strlen($description.' '.$sentence) <= 160) {
                $description .= ' '.$sentence;
            }
        }

        return $description;
    }

    private function saleIntent(string $saleType): ?string
    {
        return match (mb_strtolower(trim($saleType))) {
            'atacado' => 'no Atacado',
            'varejo' => 'no Varejo',
            'ambos' => 'no Atacado e Varejo',
            default => null,
        };
    }

    private function saleLabel(string $saleType): ?string
    {
        return match (mb_strtolower(trim($saleType))) {
            'atacado' => 'Atacado',
            'varejo' => 'Varejo',
            'ambos' => 'Atacado e Varejo',
            default => null,
        };
    }

    private function location(Team $store): ?string
    {
        if (mb_strtolower(trim((string) $store->store_type)) === 'virtual') {
            return null;
        }

        $city = $this->clean($store->city);

        return $city ? "em {$city}" : null;
    }

    /**
     * @return list<string>
     */
    private function subcategories(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): ?string => $this->clean($item),
            $value,
        )));
    }

    private function clean(mixed $value): ?string
    {
        $stringValue = $this->stringValue($value);
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags($stringValue)));

        return $clean !== '' ? $clean : null;
    }

    private function limitAtWord(string $value, int $limit, string $ending): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        $available = max(1, $limit - mb_strlen($ending));
        $shortened = rtrim(mb_substr($value, 0, $available));
        $lastSpace = mb_strrpos($shortened, ' ');
        if ($lastSpace !== false) {
            $shortened = rtrim(mb_substr($shortened, 0, $lastSpace));
        }

        return $shortened.$ending;
    }

    private function stringValue(mixed $value): string
    {
        return (is_scalar($value) || $value instanceof Stringable)
            ? trim((string) $value)
            : '';
    }
}
