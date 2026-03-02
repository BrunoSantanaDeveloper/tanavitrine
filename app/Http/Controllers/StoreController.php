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
            ->with(['category', 'photos', 'collections.media', 'plan']);

        // Only filter by 'ativo' status in production
        if (config('app.env') === 'production') {
            $query->where('status', 'ativo');
        }

        $store = $query->firstOrFail();
        $featuredMedia = $store->photos
            ->where('type', 'image')
            ->where('is_active', true)
            ->where('team_collection_id', null)
            ->where('category', '!=', 'logo')
            ->values();
        $collections = $store->collections
            ->map(function ($collection) {
                $photos = $collection->media
                    ->where('type', 'image')
                    ->where('is_active', true)
                    ->values()
                    ->map(function ($photo) {
                        return asset('storage/' . $photo->path);
                    })
                    ->toArray();

                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'description' => $collection->description,
                    'is_featured' => (bool) $collection->is_featured,
                    'photos_count' => count($photos),
                    'cover' => $photos[0] ?? null,
                    'photos' => $photos,
                ];
            })
            ->sortByDesc('is_featured')
            ->values();

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
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'whatsapp' => $store->whatsapp,
            'phone' => $store->phone,
            'email' => $store->email,
            'website' => $store->website,
            'instagram' => $store->instagram,
            'facebook' => $store->facebook,
            'tiktok' => $store->tiktok,
            'featured' => $store->isFeatured(),
            'logo' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
            'video_url' => $store->video_url,
            'images' => $featuredMedia->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => asset('storage/' . $photo->path),
                ];
            })->toArray(),
            'collections' => $collections->toArray(),
            'has_collections' => $collections->isNotEmpty(),
            'show_on_map' => $store->plan?->show_on_map ?? false,
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
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'whatsapp_clicks')) {
            $store->incrementWhatsappClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track website click.
     */
    public function trackWebsiteClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'website_clicks')) {
            $store->incrementWebsiteClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track phone click.
     */
    public function trackPhoneClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'phone_clicks')) {
            $store->incrementPhoneClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track map click.
     */
    public function trackMapClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'map_clicks')) {
            $store->incrementMapClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track Instagram click.
     */
    public function trackInstagramClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'instagram_clicks')) {
            $store->incrementInstagramClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track Facebook click.
     */
    public function trackFacebookClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'facebook_clicks')) {
            $store->incrementFacebookClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track TikTok click.
     */
    public function trackTikTokClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'tiktok_clicks')) {
            $store->incrementTikTokClicks();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Check if store has analytics access for a specific metric based on plan.
     */
    private function hasAnalyticsAccess(Team $store, string $metric): bool
    {
        if (!$store->plan) {
            return false;
        }

        return $store->plan->hasAnalyticsMetric($metric);
    }

    /**
     * Capture lead information.
     */
    public function captureLead(Request $request, string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

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

        // Track clicks based on action if plan has analytics access for that metric
        if ($validated['action'] === 'whatsapp' && $this->hasAnalyticsAccess($store, 'whatsapp_clicks')) {
            $store->incrementWhatsappClicks();
        } elseif ($validated['action'] === 'website' && $this->hasAnalyticsAccess($store, 'website_clicks')) {
            $store->incrementWebsiteClicks();
        } elseif ($validated['action'] === 'map' && $this->hasAnalyticsAccess($store, 'map_clicks')) {
            $store->increment('map_clicks');
        } elseif ($validated['action'] === 'phone' && $this->hasAnalyticsAccess($store, 'phone_clicks')) {
            $store->increment('phone_clicks');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track share action.
     */
    public function trackShare(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        // Only track if plan has analytics access for this metric
        if ($this->hasAnalyticsAccess($store, 'shares')) {
            $store->increment('shares_count');
        }

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
                $cardMedia = $store->media
                    ->where('type', 'image')
                    ->where('team_collection_id', null)
                    ->where('category', '!=', 'logo')
                    ->first();

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
                    'image' => $cardMedia?->path
                        ? asset('storage/' . $cardMedia->path)
                        : null,
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
