<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;

final class CategorySeoBuilder
{
    public function __construct(private readonly SeoMeta $seoMeta) {}

    /** @return array<string, string> */
    public function directory(int $eligibleCategoriesCount): array
    {
        return $this->seoMeta->make(
            title: 'Categorias de Fornecedores de Moda',
            description: 'Explore categorias de lojas e fornecedores de moda no atacado e varejo e fale diretamente com cada empresa.',
            canonical: '/categorias',
            indexable: $this->indexingEnabled() && $eligibleCategoriesCount > 0,
        );
    }

    /** @return array<string, string> */
    public function category(Category $category, int $storesCount, int $page): array
    {
        $name = trim((string) $category->name);
        $description = trim((string) $category->description);

        if ($description === '') {
            $description = "Encontre lojas e fornecedores de {$name} no atacado e varejo. Conheça as vitrines e fale diretamente com cada empresa.";
        }

        return $this->seoMeta->make(
            title: "Fornecedores de {$name} no Atacado e Varejo",
            description: $description,
            canonical: route('categories.show', $category->slug, false),
            indexable: $this->indexingEnabled() && $storesCount >= $this->minimumStores(),
            canonicalQuery: $page > 1 ? ['page' => $page] : [],
        );
    }

    private function indexingEnabled(): bool
    {
        return (bool) config('seo.category_indexing_enabled', false);
    }

    private function minimumStores(): int
    {
        $configured = config('seo.category_min_stores', 3);

        return max(1, is_numeric($configured) ? (int) $configured : 3);
    }
}
