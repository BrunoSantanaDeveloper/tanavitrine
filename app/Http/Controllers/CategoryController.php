<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of all categories.
     */
    public function index(): Response
    {
        $categories = Category::active()
            ->parents()
            ->with('children')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'description' => $category->description,
                    'stores_count' => $category->teams()->active()->count(),
                    'subcategories' => $category->children->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'slug' => $sub->slug,
                            'stores_count' => $sub->teams()->active()->count(),
                        ];
                    }),
                ];
            });

        return Inertia::render('Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Display stores for a specific category.
     */
    public function show(Request $request, string $slug): Response
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $query = Team::active()
            ->where('personal_team', false)
            ->where('category_id', $category->id)
            ->with(['category', 'photos']);

        // Apply filters
        if ($request->has('sale_type') && $request->sale_type !== '') {
            if ($request->sale_type === 'atacado') {
                $query->atacado();
            } elseif ($request->sale_type === 'varejo') {
                $query->varejo();
            }
        }

        if ($request->has('state') && $request->state !== '') {
            $query->where('state', $request->state);
        }

        if ($request->has('city') && $request->city !== '') {
            $query->where('city', $request->city);
        }

        if ($request->has('gender') && $request->gender !== '') {
            $query->where('gender', $request->gender);
        }

        if ($request->has('store_type') && $request->store_type !== '') {
            $query->where('store_type', $request->store_type);
        }

        // Sort: featured first, then by created_at
        $query->orderByRaw('featured DESC, created_at DESC');

        $stores = $query->paginate(12)->through(function ($store) {
            return [
                'id' => $store->id,
                'code' => 'TV' . str_pad((string)$store->id, 4, '0', STR_PAD_LEFT),
                'slug' => $store->slug,
                'name' => $store->name,
                'description' => $store->description,
                'badge' => ucfirst($store->sale_type),
                'category' => $store->category?->name,
                'subcategory' => $store->subcategory,
                'is_manufacturer' => (bool) $store->is_manufacturer,
                'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
                'min_order' => $store->min_order,
                'whatsapp' => $store->whatsapp,
                'is_verified' => $store->isVerified(),
                'featured' => $store->isFeatured(),
                'image' => $store->photos->first()?->url ?? 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800',
            ];
        });

        return Inertia::render('CategoryStores', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'stores' => $stores,
            'filters' => $request->only(['sale_type', 'state', 'city', 'gender', 'store_type']),
        ]);
    }
}
