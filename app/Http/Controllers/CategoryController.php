<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use App\Models\Media;
use Inertia\Response;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Support\CategorySeoBuilder;
use Illuminate\Support\Facades\Route;
use App\Support\CategoryStoreCardBuilder;
use Illuminate\Database\Eloquent\Builder;
use App\Support\CategoryStructuredDataBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategorySeoBuilder $seoBuilder,
        private readonly CategoryStoreCardBuilder $storeCardBuilder,
        private readonly CategoryStructuredDataBuilder $structuredDataBuilder,
    ) {}

    public function index(): Response
    {
        $this->ensureEnabled();

        $storeCounts = Team::query()
            ->active()
            ->where('personal_team', false)
            ->whereNotNull('category_id')
            ->select('category_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(static function (Team $store): array {
                $count = $store->getAttribute('aggregate');

                return [(int) $store->category_id => is_numeric($count) ? (int) $count : 0];
            });

        $categories = Category::query()
            ->active()
            ->parents()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(static fn (Category $category): bool => ($storeCounts[$category->id] ?? 0) > 0)
            ->map(static fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => route('categories.show', $category->slug),
                'icon' => $category->icon,
                'description' => $category->description,
                'stores_count' => $storeCounts[$category->id] ?? 0,
            ])
            ->values();

        $eligibleCategoriesCount = $categories
            ->where('stores_count', '>=', $this->minimumStores())
            ->count();
        $seo = $this->seoBuilder->directory($eligibleCategoriesCount);
        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Categorias', 'url' => route('categories.index'), 'current' => true],
        ];

        /** @var list<array{name: string, url: string}> $structuredItems */
        $structuredItems = $categories->map(static fn (array $category): array => [
            'name' => $category['name'],
            'url' => $category['url'],
        ])->values()->all();

        return Inertia::render('Categories', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'categories' => $categories,
            'breadcrumbs' => $breadcrumbs,
            'seo' => $seo,
            'structuredData' => $this->structuredDataBuilder->build(
                canonical: $seo['canonical'],
                title: $seo['title'],
                description: $seo['description'],
                breadcrumbs: $breadcrumbs,
                items: $structuredItems,
            ),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $this->ensureEnabled();

        $category = Category::query()
            ->active()
            ->parents()
            ->where('slug', $slug)
            ->firstOrFail();

        $page = max(1, $request->integer('page', 1));
        $stores = Team::query()
            ->active()
            ->where('personal_team', false)
            ->where('category_id', $category->id)
            ->with([
                'category',
                'photos' => static function (Relation $relation): void {
                    /** @var Builder<Media> $query */
                    $query = $relation->getQuery();
                    $query->where('type', 'image')
                        ->where('media.is_active', true)
                        ->whereNull('media.team_collection_id')
                        ->where(static fn (Builder $nestedQuery): Builder => $nestedQuery
                            ->whereNull('category')
                            ->orWhere('category', '!=', 'logo'))
                        ->orderByDesc('team_media.is_primary')
                        ->orderBy('team_media.order');
                },
            ])
            ->orderByDesc('featured')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: 12, page: $page);

        abort_if($stores->total() === 0 || $page > $stores->lastPage(), 404);

        /** @var list<array{name: string, url: string}> $structuredStoreItems */
        $structuredStoreItems = collect($stores->items())
            ->map(static fn (Team $store): array => [
                'name' => (string) $store->name,
                'url' => route('store.show', $store->slug),
            ])
            ->values()
            ->all();
        $stores->through(fn (Team $store): array => $this->storeCardBuilder->build($store));

        $seo = $this->seoBuilder->category($category, $stores->total(), $page);
        $breadcrumbs = [
            ['name' => 'Início', 'url' => route('home')],
            ['name' => 'Categorias', 'url' => route('categories.index')],
            ['name' => $category->name, 'url' => route('categories.show', $category->slug), 'current' => true],
        ];

        return Inertia::render('CategoryStores', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'stores_count' => $stores->total(),
            ],
            'stores' => $stores,
            'breadcrumbs' => $breadcrumbs,
            'seo' => $seo,
            'structuredData' => $this->structuredDataBuilder->build(
                canonical: $seo['canonical'],
                title: $seo['title'],
                description: $seo['description'],
                breadcrumbs: $breadcrumbs,
                items: $structuredStoreItems,
                positionOffset: ($page - 1) * $stores->perPage(),
            ),
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('seo.category_pages_enabled', false), 404);
    }

    private function minimumStores(): int
    {
        $configured = config('seo.category_min_stores', 3);

        return max(1, is_numeric($configured) ? (int) $configured : 3);
    }
}
