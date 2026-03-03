<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class WelcomeController extends Controller
{
    public function home(): Response
    {
        // Get featured stores
        $featuredStores = Team::active()
            ->where('personal_team', false)
            ->featured()
            ->with(['category', 'media', 'plan'])
            ->limit(6)
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Get recent stores (including featured ones)
        $recentStores = Team::active()
            ->where('personal_team', false)
            ->with(['category', 'media', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Category filters for home tabs (only categories that have active stores in each context).
        $categoriesAtacado = $this->buildCategoryOptionsFromTeams(
            Team::active()
                ->where('personal_team', false)
                ->atacado()
                ->with('category')
                ->get()
        );

        $categoriesVarejo = $this->buildCategoryOptionsFromTeams(
            Team::active()
                ->where('personal_team', false)
                ->varejo()
                ->with('category')
                ->get()
        );

        // Get unique states from active stores for home filters
        $states = Team::active()
            ->where('personal_team', false)
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'featuredStores' => $featuredStores,
            'recentStores' => $recentStores,
            'categoriesAtacado' => $categoriesAtacado,
            'categoriesVarejo' => $categoriesVarejo,
            'states' => $states,
            'plans' => Plan::where('is_active', true)
                ->with('intervals')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($plan) {
                    $intervals = $plan->intervals->map(function ($interval) {
                        return [
                            'id' => $interval->pivot->id,
                            'name' => $interval->name,
                            'code' => $interval->code,
                            'description' => $interval->description,
                            'price' => (float) $interval->pivot->price,
                        ];
                    });

                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'intervals' => $intervals,
                        'currency' => $plan->currency,
                        'features' => $plan->features,
                        'is_featured' => $plan->is_featured,
                        'metadata' => $plan->metadata,
                    ];
                }),
            'seo' => [
                'title' => config('app.name') . ' - O maior catálogo de fornecedores de moda do Brasil',
                'description' => 'Conecte-se com as melhores lojas e fornecedores de moda. Atacado e varejo com os melhores preços.',
            ],
        ]);
    }

    public function atacado(): Response
    {
        // Get all atacado stores (no limit for client-side filtering)
        $allStores = Team::active()
            ->where('personal_team', false)
            ->atacado()
            ->with(['category', 'media', 'plan'])
            ->orderByDesc('featured')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Get unique states from atacado stores
        $states = Team::active()
            ->where('personal_team', false)
            ->atacado()
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        // Get unique cities from atacado stores
        $cities = Team::active()
            ->where('personal_team', false)
            ->atacado()
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        [$categories, $subcategories] = $this->buildFilterOptionsFromStores($allStores);

        return Inertia::render('Atacado', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'stores' => $allStores,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'states' => $states,
            'cities' => $cities,
            'seo' => [
                'title' => 'Atacado - ' . config('app.name'),
                'description' => 'Encontre os melhores fornecedores atacadistas de moda do Brasil',
            ],
        ]);
    }

    public function varejo(): Response
    {
        // Get all varejo stores (no limit for client-side filtering)
        $allStores = Team::active()
            ->where('personal_team', false)
            ->varejo()
            ->with(['category', 'media', 'plan'])
            ->orderByDesc('featured')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Get unique states from varejo stores
        $states = Team::active()
            ->where('personal_team', false)
            ->varejo()
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        // Get unique cities from varejo stores
        $cities = Team::active()
            ->where('personal_team', false)
            ->varejo()
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        [$categories, $subcategories] = $this->buildFilterOptionsFromStores($allStores);

        return Inertia::render('Varejo', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'stores' => $allStores,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'states' => $states,
            'cities' => $cities,
            'seo' => [
                'title' => 'Varejo - ' . config('app.name'),
                'description' => 'Descubra as melhores lojas varejistas de moda',
            ],
        ]);
    }

    public function prices(): Response
    {
        return Inertia::render('Prices', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'plans' => Plan::where('is_active', true)
                ->with('intervals')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($plan) {
                    $intervals = $plan->intervals->map(function ($interval) {
                        return [
                            'id' => $interval->pivot->id,
                            'name' => $interval->name,
                            'code' => $interval->code,
                            'description' => $interval->description,
                            'price' => (float) $interval->pivot->price,
                        ];
                    });

                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'intervals' => $intervals,
                        'currency' => $plan->currency,
                        'features' => $plan->features,
                        'is_featured' => $plan->is_featured,
                        'metadata' => $plan->metadata,
                    ];
                }),
            'seo' => [
                'title' => 'Planos - ' . config('app.name'),
                'description' => 'Escolha o plano ideal para anunciar seus produtos no TanaVitrine',
            ],
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'seo' => [
                'title' => 'Sobre - ' . config('app.name'),
                'description' => 'Conheça a TanaVitrine, o maior marketplace de moda atacado e varejo do Brasil. Conectando fornecedores e lojistas.',
            ],
        ]);
    }

    /**
     * Load more stores for infinite scroll.
     */
    public function loadMoreStores(): \Illuminate\Http\JsonResponse
    {
        $type = request('type', 'recentes'); // 'destaques' or 'recentes'
        $page = request('page', 1);
        $perPage = 6;

        $query = Team::active()
            ->where('personal_team', false)
            ->with(['category', 'media', 'plan']);

        if ($type === 'destaques') {
            $query->featured();
        } else {
            // For recentes, show all stores ordered by creation date
            $query->orderBy('created_at', 'desc');
        }

        $stores = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        $totalCount = Team::active()
            ->where('personal_team', false)
            ->when($type === 'destaques', fn($q) => $q->featured())
            ->count();

        return response()->json([
            'stores' => $stores,
            'hasMore' => ($page * $perPage) < $totalCount,
            'nextPage' => $page + 1,
        ]);
    }

    /**
     * Transform store model to array for frontend.
     */
    private function transformStore($store): array
    {
        $photos = $store->media
            ->where('type', 'image')
            ->where('is_active', true)
            ->where('category', '!=', 'logo')
            ->values()
            ->map(fn ($photo) => $photo->url)
            ->toArray();

        return [
            'id' => $store->id,
            'code' => 'TV' . str_pad((string)$store->id, 4, '0', STR_PAD_LEFT),
            'slug' => $store->slug,
            'url' => route('store.show', $store->slug),
            'badge' => ucfirst($store->sale_type),
            'name' => $store->name,
            'category' => $store->category?->name,
            'subcategory' => $store->subcategory,
            'description' => $store->description,
            'saleType' => ucfirst($store->sale_type),
            'storeType' => ucfirst((string) $store->store_type),
            'minOrder' => $store->min_order,
            'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
            'city' => $store->city,
            'state' => $store->state,
            'latitude' => $store->latitude ? (float) $store->latitude : null,
            'longitude' => $store->longitude ? (float) $store->longitude : null,
            'whatsapp' => $store->whatsapp,
            'logo' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
            'image' => $photos[0] ?? null,
            'images' => $photos,
            'featured' => $store->isFeatured(),
            'show_on_map' => (bool) ($store->currentPlan()?->show_on_map ?? false),
            'can_favorite' => auth()->check(),
            'is_favorited' => auth()->check()
                ? auth()->user()->favoriteStores()->where('team_id', $store->id)->exists()
                : false,
        ];
    }

    /**
     * Alternate featured and recent stores.
     */
    private function alternateStores($featured, $recent): array
    {
        $result = [];
        $maxLength = max($featured->count(), $recent->count());

        for ($i = 0; $i < $maxLength; $i++) {
            if (isset($featured[$i])) {
                $result[] = $featured[$i];
            }
            if (isset($recent[$i])) {
                $result[] = $recent[$i];
            }
        }

        return $result;
    }

    /**
     * Build category/subcategory filter options based on active stores in the current listing.
     *
     * @return array{0: array<int, array{name: string, count: int}>, 1: array<int, array{name: string, count: int}>}
     */
    private function buildFilterOptionsFromStores(Collection $stores): array
    {
        $categoryCounts = [];
        $subcategoryCounts = [];

        foreach ($stores as $store) {
            $categoryName = trim((string) ($store['category'] ?? ''));
            if ($categoryName !== '') {
                $categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;
            }

            $subcategory = $store['subcategory'] ?? [];
            $subItems = is_array($subcategory) ? $subcategory : [$subcategory];

            foreach ($subItems as $sub) {
                $subName = trim((string) $sub);
                if ($subName === '') {
                    continue;
                }
                $subcategoryCounts[$subName] = ($subcategoryCounts[$subName] ?? 0) + 1;
            }
        }

        ksort($categoryCounts, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($subcategoryCounts, SORT_NATURAL | SORT_FLAG_CASE);

        $categories = collect($categoryCounts)->map(
            fn (int $count, string $name) => ['name' => $name, 'count' => $count]
        )->values()->all();

        $subcategories = collect($subcategoryCounts)->map(
            fn (int $count, string $name) => ['name' => $name, 'count' => $count]
        )->values()->all();

        return [$categories, $subcategories];
    }

    /**
     * Build category filter options (name + count) from a team collection.
     *
     * @return array<int, array{name: string, count: int}>
     */
    private function buildCategoryOptionsFromTeams(Collection $teams): array
    {
        $categoryCounts = [];

        foreach ($teams as $team) {
            $categoryName = trim((string) ($team->category?->name ?? ''));
            if ($categoryName === '') {
                continue;
            }
            $categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;
        }

        ksort($categoryCounts, SORT_NATURAL | SORT_FLAG_CASE);

        return collect($categoryCounts)->map(
            fn (int $count, string $name) => ['name' => $name, 'count' => $count]
        )->values()->all();
    }
}
