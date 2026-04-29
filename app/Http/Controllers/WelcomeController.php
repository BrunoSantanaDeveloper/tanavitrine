<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

final class WelcomeController extends Controller
{
    public function home(): Response
    {
        // Get all featured stores and rotate the top store daily.
        $featuredStores = $this->rotateFeaturedStores(
            Team::active()
            ->where('personal_team', false)
            ->featured()
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
        )
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Get recent stores (including featured ones)
        $recentStores = Team::active()
            ->where('personal_team', false)
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
            ->orderBy('created_at', 'desc')
            ->orderByDesc('id')
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
            ->whereIn('store_type', ['ambos', 'fisica'])
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
                'title' => 'Tá na Vitrine - O maior catálogo de fornecedores de moda do Brasil',
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
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
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
            ->whereIn('store_type', ['ambos', 'fisica'])
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        // Get unique cities from atacado stores
        $cities = Team::active()
            ->where('personal_team', false)
            ->atacado()
            ->whereIn('store_type', ['ambos', 'fisica'])
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
                'title' => 'Atacado - Tá na Vitrine',
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
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
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
            ->whereIn('store_type', ['ambos', 'fisica'])
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        // Get unique cities from varejo stores
        $cities = Team::active()
            ->where('personal_team', false)
            ->varejo()
            ->whereIn('store_type', ['ambos', 'fisica'])
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
                'title' => 'Varejo - Tá na Vitrine',
                'description' => 'Descubra as melhores lojas varejistas de moda',
            ],
        ]);
    }

    public function fabricantes(): Response
    {
        // Get all manufacturer stores (no limit for client-side filtering)
        $allStores = Team::active()
            ->where('personal_team', false)
            ->where('is_manufacturer', true)
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
            ->orderByDesc('featured')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            });

        // Get unique states from manufacturer stores
        $states = Team::active()
            ->where('personal_team', false)
            ->where('is_manufacturer', true)
            ->whereIn('store_type', ['ambos', 'fisica'])
            ->whereNotNull('state')
            ->distinct()
            ->pluck('state')
            ->sort()
            ->values();

        // Get unique cities from manufacturer stores
        $cities = Team::active()
            ->where('personal_team', false)
            ->where('is_manufacturer', true)
            ->whereIn('store_type', ['ambos', 'fisica'])
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        [$categories, $subcategories] = $this->buildFilterOptionsFromStores($allStores);

        return Inertia::render('Fabricantes', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'stores' => $allStores,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'states' => $states,
            'cities' => $cities,
            'seo' => [
                'title' => 'Fabricantes - Tá na Vitrine',
                'description' => 'Conheça fabricantes com produção própria na Tá na Vitrine',
            ],
        ]);
    }

    public function mapaDeLojas(): Response
    {
        $stores = Team::active()
            ->where('personal_team', false)
            ->featured()
            ->whereIn('store_type', ['ambos', 'fisica'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }])
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($store) {
                return $this->transformStore($store);
            })
            ->values();

        return Inertia::render('MapaDeLojas', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'stores' => $stores,
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
                'title' => 'Planos - Tá na Vitrine',
                'description' => 'Escolha o plano ideal para anunciar seus produtos no Tá na Vitrine',
            ],
        ]);
    }

    public function about(): Response
    {
        $aboutMetrics = Cache::remember('about:metrics:v1', now()->addMinutes(15), function (): array {
            $activeStoresQuery = Team::active()
                ->where('personal_team', false);

            $activeStoresCount = (clone $activeStoresQuery)->count();
            $statesWithPhysicalStoresCount = (clone $activeStoresQuery)
                ->whereIn('store_type', ['ambos', 'fisica'])
                ->whereNotNull('state')
                ->distinct()
                ->count('state');
            $manufacturersCount = (clone $activeStoresQuery)
                ->where('is_manufacturer', true)
                ->count();
            $verifiedStoresCount = (clone $activeStoresQuery)
                ->where('is_verified', true)
                ->count();

            return [
                'updated_at' => now()->format('d/m/Y H:i'),
                'items' => [
                    [
                        'key' => 'active_stores',
                        'label' => 'Lojas ativas',
                        'value' => $activeStoresCount,
                        'description' => 'Operando na plataforma com presença pública.',
                    ],
                    [
                        'key' => 'physical_states',
                        'label' => 'Estados com lojas físicas',
                        'value' => $statesWithPhysicalStoresCount,
                        'description' => 'Capilaridade de operação física no Brasil.',
                    ],
                    [
                        'key' => 'manufacturers',
                        'label' => 'Fabricantes na rede',
                        'value' => $manufacturersCount,
                        'description' => 'Marcas com produção própria cadastradas.',
                    ],
                    [
                        'key' => 'verified_stores',
                        'label' => 'Lojas verificadas',
                        'value' => $verifiedStoresCount,
                        'description' => 'Perfis com verificação concluída.',
                    ],
                ],
            ];
        });

        return Inertia::render('About', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'lastUpdated' => $aboutMetrics['updated_at'] ?? '',
            'metrics' => $aboutMetrics['items'] ?? [],
            'seo' => [
                'title' => 'Sobre a Tá na Vitrine',
                'description' => 'Conheça a história, os diferenciais e os números atuais da Tá na Vitrine para atacado, varejo e fabricantes.',
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
            ->with(['category', 'plan', 'photos' => function ($query): void {
                $query->where('type', 'image')
                    ->where('media.is_active', true)
                    ->whereNull('media.team_collection_id')
                    ->where(function ($nestedQuery): void {
                        $nestedQuery->whereNull('category')->orWhere('category', '!=', 'logo');
                    })
                    ->orderByDesc('team_media.is_primary')
                    ->orderBy('team_media.order');
            }]);

        if ($type === 'destaques') {
            $query->featured()
                ->orderByDesc('featured_until')
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        } else {
            // For recentes, show all stores ordered by creation date
            $query->orderBy('created_at', 'desc')
                ->orderByDesc('id');
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
        $photos = $store->photos
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
            'gender' => $store->gender,
            'is_manufacturer' => (bool) $store->is_manufacturer,
            'description' => $store->description,
            'saleType' => ucfirst($store->sale_type),
            'storeType' => ucfirst((string) $store->store_type),
            'minOrder' => $store->min_order,
            'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
            'city' => $store->city,
            'state' => $store->state,
            'latitude' => $store->latitude ? (float) $store->latitude : null,
            'longitude' => $store->longitude ? (float) $store->longitude : null,
            'google_maps_embed_url' => $store->google_maps_embed_url,
            'whatsapp' => $store->whatsapp,
            'logo' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
            'image' => $photos[0] ?? null,
            'images' => $photos,
            'is_verified' => $store->isVerified(),
            'featured' => $store->isFeatured(),
            'show_on_map' => (bool) ($store->currentPlan()?->show_on_map ?? false),
            'can_favorite' => auth()->check(),
            'is_favorited' => auth()->check()
                ? auth()->user()->favoriteStores()->where('team_id', $store->id)->exists()
                : false,
        ];
    }

    /**
     * Rotate featured stores so one store reaches the top once per cycle/day.
     */
    private function rotateFeaturedStores(Collection $stores): Collection
    {
        if ($stores->isEmpty()) {
            return $stores;
        }

        $storeIds = $stores->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $topStoreId = $this->consumeNextFeaturedTopStore($storeIds);

        if ($topStoreId === null) {
            return $stores->shuffle()->values();
        }

        $remainingIds = array_values(array_filter(
            $storeIds,
            fn (int $id): bool => $id !== $topStoreId
        ));
        shuffle($remainingIds);

        $orderedIds = array_merge([$topStoreId], $remainingIds);
        $orderMap = array_flip($orderedIds);

        return $stores->sortBy(
            fn ($store): int => $orderMap[(int) $store->id] ?? PHP_INT_MAX
        )->values();
    }

    /**
     * Returns and consumes the next featured store id for the current day cycle.
     */
    private function consumeNextFeaturedTopStore(array $availableIds): ?int
    {
        $availableIds = array_values(array_unique(array_map('intval', $availableIds)));

        if ($availableIds === []) {
            return null;
        }

        sort($availableIds);

        $today = now()->toDateString();
        $queueKey = "home:featured-top-rotation:{$today}";
        $expiresAt = now()->endOfDay();
        $lockKey = "{$queueKey}:lock";

        $resolver = function () use ($availableIds, $queueKey, $expiresAt): ?int {
            $queue = Cache::get($queueKey, []);
            if (! is_array($queue)) {
                $queue = [];
            }

            $availableSet = array_flip($availableIds);
            $queue = array_values(array_filter(
                array_map('intval', $queue),
                fn (int $id): bool => isset($availableSet[$id])
            ));

            if ($queue === []) {
                $queue = $availableIds;
                shuffle($queue);
            }

            $topStoreId = array_shift($queue);
            if ($topStoreId === null) {
                return null;
            }

            // Restart cycle as soon as all stores have reached the top.
            if ($queue === []) {
                $queue = $availableIds;
                shuffle($queue);

                if (count($queue) > 1 && (int) $queue[0] === (int) $topStoreId) {
                    $first = array_shift($queue);
                    $queue[] = $first;
                }
            }

            Cache::put($queueKey, $queue, $expiresAt);

            return (int) $topStoreId;
        };

        if (method_exists(Cache::getStore(), 'lock')) {
            return Cache::lock($lockKey, 3)->block(2, $resolver);
        }

        return $resolver();
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
