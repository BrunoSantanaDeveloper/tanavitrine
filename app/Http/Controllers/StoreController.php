<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Route;

class StoreController extends Controller
{
    /**
     * Display the specified store by slug.
     */
    public function show(string $slug): Response
    {
        $query = Team::where('slug', $slug)
            ->where('personal_team', false)
            ->with(['category', 'media']);

        // Only filter by 'ativo' status in production
        if (config('app.env') === 'production') {
            $query->where('status', 'ativo');
        }

        $store = $query->firstOrFail();

        // Increment views
        $store->incrementViews();

        // Transform data for frontend
        $storeData = [
            'id' => $store->id,
            'code' => 'TV' . str_pad((string)$store->id, 4, '0', STR_PAD_LEFT),
            'slug' => $store->slug,
            'name' => $store->name,
            'description' => $store->description,
            'badge' => ucfirst($store->sale_type),
            'category' => $store->category?->name,
            'subcategory' => $store->subcategory,
            'gender' => $store->gender,
            'saleType' => ucfirst($store->sale_type),
            'storeType' => ucfirst($store->store_type),
            'minOrder' => $store->min_order,
            'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
            'address' => $store->address,
            'whatsapp' => $store->whatsapp,
            'phone' => $store->phone,
            'email' => $store->email,
            'website' => $store->website,
            'instagram' => $store->instagram,
            'facebook' => $store->facebook,
            'tiktok' => $store->tiktok,
            'featured' => $store->isFeatured(),
            'logo' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
            'images' => $store->media->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => asset('storage/' . $photo->path),
                ];
            })->toArray(),
            'views_count' => $store->views_count,
            'is_favorited' => auth()->check()
                ? auth()->user()->favoriteStores()->where('team_id', $store->id)->exists()
                : false,
        ];

        return Inertia::render('StoreDetail', [
            'store' => $storeData,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    /**
     * Track WhatsApp click.
     */
    public function trackWhatsAppClick(string $slug)
    {
        $store = Team::where('slug', $slug)->firstOrFail();
        $store->incrementWhatsappClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track website click.
     */
    public function trackWebsiteClick(string $slug)
    {
        $store = Team::where('slug', $slug)->firstOrFail();
        $store->incrementWebsiteClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Capture lead information.
     */
    public function captureLead(Request $request, string $slug)
    {
        $store = Team::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:20',
            'action' => 'required|in:whatsapp,map,website,phone',
        ]);

        // Create lead
        $store->leads()->create([
            'name' => $validated['name'],
            'whatsapp' => $validated['whatsapp'],
            'action' => $validated['action'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Increment respective counter
        if ($validated['action'] === 'whatsapp') {
            $store->incrementWhatsappClicks();
        } elseif ($validated['action'] === 'website') {
            $store->incrementWebsiteClicks();
        } elseif ($validated['action'] === 'map') {
            $store->increment('map_clicks');
        } elseif ($validated['action'] === 'phone') {
            $store->increment('phone_clicks');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track share action.
     */
    public function trackShare(string $slug)
    {
        $store = Team::where('slug', $slug)->firstOrFail();
        $store->increment('shares_count');

        return response()->json(['success' => true]);
    }

    /**
     * Toggle favorite status for a store.
     */
    public function toggleFavorite(string $slug)
    {
        $store = Team::where('slug', $slug)
            ->where('personal_team', false)
            ->firstOrFail();

        $user = auth()->user();

        // Check if already favorited
        $favorite = $user->favoriteStores()->where('team_id', $store->id)->first();

        if ($favorite) {
            // Unfavorite
            $user->favoriteStores()->detach($store->id);
            $isFavorited = false;
        } else {
            // Favorite
            $user->favoriteStores()->attach($store->id);
            $isFavorited = true;
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
        ]);
    }

    /**
     * Display user's favorited stores.
     */
    public function favorites(): Response
    {
        $user = auth()->user();

        $favoriteStores = $user->favoriteStores()
            ->with(['category', 'media'])
            ->orderBy('favorites.created_at', 'desc')
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'code' => 'TV' . str_pad((string)$store->id, 4, '0', STR_PAD_LEFT),
                    'slug' => $store->slug,
                    'badge' => ucfirst($store->sale_type),
                    'name' => $store->name,
                    'category' => $store->category?->name,
                    'subcategory' => $store->subcategory,
                    'description' => $store->description,
                    'saleType' => ucfirst($store->sale_type),
                    'minOrder' => $store->min_order,
                    'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
                    'whatsapp' => $store->whatsapp,
                    'image' => $store->media->first()?->path
                        ? asset('storage/' . $store->media->first()->path)
                        : 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800',
                    'featured' => $store->isFeatured(),
                    'favorited_at' => $store->pivot->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Favorites', [
            'stores' => $favoriteStores,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'seo' => [
                'title' => 'Meus Favoritos - ' . config('app.name'),
                'description' => 'Suas lojas favoritas salvas',
            ],
        ]);
    }
}
