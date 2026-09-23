<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;
use App\Support\SeoMeta;
use App\Support\StoreMinimumOrder;
use App\Support\StoreSeoBuilder;
use App\Support\StoreSocialUrl;
use App\Support\StoreStructuredDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

final class StoreController extends Controller
{
    public function __construct(
        private readonly SeoMeta $seoMeta,
        private readonly StoreSeoBuilder $storeSeoBuilder,
        private readonly StoreStructuredDataBuilder $structuredDataBuilder,
    ) {}

    /**
     * Display the specified store by slug.
     */
    public function show(string $slug): Response
    {
        $query = Team::where('slug', $slug)
            ->where('personal_team', false)
            ->with(['category', 'photos', 'collections.media', 'plan']);

        // Sempre respeita regras de acesso público (ativo/trial/expirado).
        $query->active();

        $store = $query->firstOrFail();
        $featuredMedia = $store->photos
            ->where('type', 'image')
            ->where('is_active', true)
            ->where('team_collection_id', null)
            ->where('category', '!=', 'logo')
            ->values();
        $primaryFeaturedMedia = $featuredMedia->first(
            static fn ($photo): bool => (bool) ($photo->pivot?->is_primary ?? false)
        );
        $logoMedia = $store->photos
            ->where('type', 'image')
            ->where('is_active', true)
            ->where('category', 'logo')
            ->first();
        $collections = $store->collections
            ->map(function ($collection) {
                $photos = $collection->media
                    ->where('type', 'image')
                    ->where('is_active', true)
                    ->values()
                    ->map(function ($photo) {
                        return $this->mediaUrl($photo->path);
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
        $logoUrl = $store->logo_path
            ? $this->mediaUrl($store->logo_path)
            : ($logoMedia?->path ? $this->mediaUrl($logoMedia->path) : null);
        $minimumOrder = in_array($store->sale_type, ['atacado', 'ambos'], true)
            ? StoreMinimumOrder::label($store->min_order)
            : null;
        $websiteUrl = StoreSocialUrl::website($store->website);
        $instagramUrl = StoreSocialUrl::instagram($store->instagram);
        $facebookUrl = StoreSocialUrl::facebook($store->facebook);
        $tiktokUrl = StoreSocialUrl::tiktok($store->tiktok);

        $storeData = [
            'id' => $store->id,
            'code' => 'TV'.mb_str_pad((string) $store->id, 4, '0', STR_PAD_LEFT),
            'slug' => $store->slug,
            'name' => $store->name,
            'description' => $store->description,
            'badge' => ucfirst($store->sale_type),
            'category' => $store->category?->name,
            'subcategory' => $store->subcategory,
            'gender' => $store->gender,
            'is_manufacturer' => (bool) $store->is_manufacturer,
            'saleType' => ucfirst($store->sale_type),
            'storeType' => ucfirst($store->store_type),
            'minOrder' => $minimumOrder,
            'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
            'city' => $store->city,
            'state' => $store->state,
            'full_address' => $this->formatFullAddress($store),
            'address' => $store->address,
            'address_number' => $store->address_number,
            'address_complement' => $store->address_complement,
            'google_maps_url' => $store->google_maps_url,
            'google_maps_embed_url' => $store->google_maps_embed_url,
            'zip_code' => $store->zip_code,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'whatsapp' => $store->whatsapp,
            'phone' => $store->phone,
            'email' => $store->email,
            'website' => $websiteUrl,
            'instagram' => $instagramUrl,
            'facebook' => $facebookUrl,
            'tiktok' => $tiktokUrl,
            'is_verified' => $store->isVerified(),
            'featured' => $store->isFeatured(),
            'logo' => $logoUrl,
            'video_url' => $store->video_url,
            'images' => $featuredMedia->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $this->mediaUrl($photo->path),
                    'is_primary' => (bool) ($photo->pivot?->is_primary ?? false),
                ];
            })->toArray(),
            'collections' => $collections->toArray(),
            'has_collections' => $collections->isNotEmpty(),
            'show_on_map' => (bool) ($store->currentPlan()?->show_on_map ?? false),
            'views_count' => $store->views_count,
            'is_favorited' => auth()->check()
                ? auth()->user()->favoriteStores()->where('team_id', $store->id)->exists()
                : false,
        ];

        $seoImage = $primaryFeaturedMedia?->path
            ? $this->mediaUrl($primaryFeaturedMedia->path)
            : ($logoUrl ?: ($featuredMedia->first()?->path
                ? $this->mediaUrl($featuredMedia->first()->path)
                : asset('images/og.png')));
        $seo = $this->storeSeoBuilder->build($store, $seoImage);
        $structuredData = $this->structuredDataBuilder->build(
            store: $store,
            canonical: $seo['canonical'],
            description: $seo['description'],
            image: $seo['ogImage'],
            sameAs: array_values(array_filter([
                $websiteUrl,
                $instagramUrl,
                $facebookUrl,
                $tiktokUrl,
            ])),
        );

        return Inertia::render('StoreDetail', [
            'store' => $storeData,
            'seo' => $seo,
            'structuredData' => $structuredData,
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

        $store->incrementWhatsappClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track website click.
     */
    public function trackWebsiteClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementWebsiteClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track phone click.
     */
    public function trackPhoneClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementPhoneClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track map click.
     */
    public function trackMapClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementMapClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track Instagram click.
     */
    public function trackInstagramClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementInstagramClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track Facebook click.
     */
    public function trackFacebookClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementFacebookClicks();

        return response()->json(['success' => true]);
    }

    /**
     * Track TikTok click.
     */
    public function trackTikTokClick(string $slug)
    {
        $store = Team::where('slug', $slug)->with('plan')->firstOrFail();

        $store->incrementTikTokClicks();

        return response()->json(['success' => true]);
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

        // Track every interaction for administrative reports. Plan rules only
        // control which analytics the store owner is allowed to see.
        if ($validated['action'] === 'whatsapp') {
            $store->incrementWhatsappClicks();
        } elseif ($validated['action'] === 'website') {
            $store->incrementWebsiteClicks();
        } elseif ($validated['action'] === 'map') {
            $store->incrementMapClicks();
        } elseif ($validated['action'] === 'phone') {
            $store->incrementPhoneClicks();
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
                    'code' => 'TV'.mb_str_pad((string) $store->id, 4, '0', STR_PAD_LEFT),
                    'slug' => $store->slug,
                    'badge' => ucfirst($store->sale_type),
                    'name' => $store->name,
                    'category' => $store->category?->name,
                    'subcategory' => $store->subcategory,
                    'is_manufacturer' => (bool) $store->is_manufacturer,
                    'description' => $store->description,
                    'saleType' => ucfirst($store->sale_type),
                    'minOrder' => $store->min_order,
                    'location' => $store->city && $store->state ? "{$store->city} - {$store->state}" : null,
                    'whatsapp' => $store->whatsapp,
                    'image' => $cardMedia?->path
                        ? asset('storage/'.$cardMedia->path)
                        : null,
                    'featured' => $store->isFeatured(),
                    'favorited_at' => $store->pivot->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('Favorites', [
            'stores' => $favoriteStores,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'seo' => $this->seoMeta->make(
                title: 'Meus favoritos',
                description: 'Suas lojas favoritas salvas',
                canonical: '/favoritos',
                indexable: false,
            ),
        ]);
    }

    private function formatFullAddress(Team $store): ?string
    {
        $streetLine = trim(implode(', ', array_filter([
            $store->address,
            $store->address_number,
        ])));

        $cityState = trim(implode(' - ', array_filter([
            $store->city,
            $store->state,
        ])));

        $parts = array_filter([
            $streetLine ?: null,
            $store->address_complement ?: null,
            $cityState ?: null,
            $store->zip_code ? 'CEP: '.$store->zip_code : null,
        ]);

        return ! empty($parts) ? implode(', ', $parts) : null;
    }

    private function mediaUrl(string $path): string
    {
        return filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : asset('storage/'.$path);
    }

    /**
     * Check if store has analytics access for a specific metric based on plan.
     */
    private function hasAnalyticsAccess(Team $store, string $metric): bool
    {
        if (! $store->plan) {
            return false;
        }

        return $store->plan->hasAnalyticsMetric($metric);
    }
}
