<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class StoreRecommendations
{
    public function forStore(Team $store): array
    {
        $subcategories = collect((array) $store->subcategory)
            ->filter(fn ($name): bool => is_string($name))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->values();

        if (! $store->category_id && $subcategories->isEmpty()) {
            return [];
        }

        $query = Team::active()
            ->where('personal_team', false)
            ->where('id', '!=', $store->id)
            ->when($store->category_id, fn (Builder $query) => $query->where('category_id', $store->category_id))
            ->inRandomOrder();

        $suggestions = new Collection();
        if ($subcategories->isNotEmpty()) {
            $suggestions = (clone $query)
                ->where(function (Builder $query) use ($subcategories): void {
                    foreach ($subcategories as $subcategory) {
                        $query->orWhereJsonContains('subcategory', $subcategory);
                    }
                })
                ->limit(3)
                ->get();
        }

        if ($store->category_id && $suggestions->count() < 3) {
            $suggestions = $suggestions->concat(
                (clone $query)->whereNotIn('id', $suggestions->modelKeys())
                    ->limit(3 - $suggestions->count())
                    ->get()
            );
        }

        $suggestions->load(['category', 'photos' => function ($query): void {
            $query->where('type', 'image')
                ->where('media.is_active', true)
                ->whereNull('media.team_collection_id')
                ->where(fn ($query) => $query->whereNull('category')->orWhere('category', '!=', 'logo'))
                ->reorder()
                ->orderByDesc('team_media.is_primary')
                ->orderBy('team_media.order');
        }]);

        return $suggestions->map(function (Team $suggestion) use ($subcategories): array {
            $sharedSubcategory = $subcategories->first(fn ($name): bool => in_array($name, (array) $suggestion->subcategory, true));

            return [
                'id' => $suggestion->id,
                'slug' => $suggestion->slug,
                'name' => $suggestion->name,
                'image' => $suggestion->photos->first()?->url,
                'category' => $suggestion->category?->name,
                'subcategory' => $sharedSubcategory,
                'saleType' => $suggestion->sale_type === 'ambos' ? 'Atacado e varejo' : ucfirst((string) $suggestion->sale_type),
            ];
        })->values()->all();
    }
}
