<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * Display the user's favorite stores.
     */
    public function index(): Response
    {
        $user = auth()->user();

        $favorites = $user->load(['favoriteStores' => function ($query) {
            $query->where('status', 'ativo')
                ->where('personal_team', false)
                ->with(['category', 'photos']);
        }]);

        $stores = $favorites->favoriteStores->map(function ($store) {
            return [
                'id' => $store->id,
                'code' => 'TV' . str_pad((string)$store->id, 4, '0', STR_PAD_LEFT),
                'slug' => $store->slug,
                'name' => $store->name,
                'description' => $store->description,
                'badge' => ucfirst($store->sale_type),
                'category' => $store->category?->name,
                'subcategory' => $store->subcategory,
                'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
                'min_order' => $store->min_order,
                'whatsapp' => $store->whatsapp,
                'is_verified' => $store->isVerified(),
                'featured' => $store->isFeatured(),
                'image' => $store->photos->first()?->url ?? 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800',
            ];
        });

        return Inertia::render('Favorites', [
            'stores' => $stores,
        ]);
    }

    /**
     * Add a store to favorites.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_slug' => ['required', 'string', 'exists:teams,slug'],
        ]);

        $store = Team::where('slug', $validated['store_slug'])
            ->where('status', 'ativo')
            ->where('personal_team', false)
            ->firstOrFail();

        $user = auth()->user();

        // Check if already favorited
        if ($user->favoriteStores()->where('team_id', $store->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Loja já está nos favoritos',
            ], 409);
        }

        $user->favoriteStores()->attach($store->id);

        return response()->json([
            'success' => true,
            'message' => 'Loja adicionada aos favoritos',
        ]);
    }

    /**
     * Remove a store from favorites.
     */
    public function destroy(string $slug): JsonResponse
    {
        $store = Team::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        $user->favoriteStores()->detach($store->id);

        return response()->json([
            'success' => true,
            'message' => 'Loja removida dos favoritos',
        ]);
    }

    /**
     * Check if a store is favorited.
     */
    public function check(string $slug): JsonResponse
    {
        $store = Team::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        $isFavorited = $user->favoriteStores()->where('team_id', $store->id)->exists();

        return response()->json([
            'is_favorited' => $isFavorited,
        ]);
    }
}
