<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DashboardStoreController extends Controller
{
    /**
     * Show the form for creating a new store.
     */
    public function create(): Response
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
                    'subcategories' => $category->children->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                        ];
                    }),
                ];
            });

        return Inertia::render('Dashboard/CreateStore', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created store.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'sale_type' => ['required', 'in:atacado,varejo,ambos'],
            'store_type' => ['required', 'in:fisica,virtual,ambos'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory' => ['required', 'array'],
            'subcategory.*' => ['string', 'max:255'],
            'gender' => ['nullable', 'in:masculino,feminino,unissex'],
            'min_order' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:50'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'video_url' => ['nullable', 'string', 'max:500'],
        ]);

        // Generate unique slug
        $slug = Str::slug($validated['name']);
        $count = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['name']) . '-' . $count;
            $count++;
        }

        $validated['slug'] = $slug;
        $validated['user_id'] = auth()->id();
        $validated['personal_team'] = false;
        $validated['status'] = 'pendente'; // Needs approval

        $store = Team::create($validated);

        return redirect()->route('dashboard.stores.edit', $store->slug)
            ->with('success', 'Vitrine criada com sucesso! Adicione fotos para completar.');
    }

    /**
     * Display the user's store dashboard.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $stores = $user->ownedTeams()
            ->where('personal_team', false)
            ->with(['category', 'photos', 'plan'])
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'slug' => $store->slug,
                    'name' => $store->name,
                    'description' => $store->description,
                    'category' => $store->category?->name,
                    'status' => $store->status,
                    'featured' => $store->isFeatured(),
                    'sale_type' => $store->sale_type,
                    'views_count' => $store->views_count,
                    'whatsapp_clicks' => $store->whatsapp_clicks,
                    'website_clicks' => $store->website_clicks,
                    'photos_count' => $store->photos->count(),
                    'max_photos' => $store->plan?->limits()
                        ->where('module', 'store')
                        ->where('resource', 'photos_per_vitrine')
                        ->value('limit_value') ?? 3,
                ];
            });

        return Inertia::render('Dashboard/MyStores', [
            'stores' => $stores,
        ]);
    }

    /**
     * Show the form for editing the specified store.
     */
    public function edit(string $slug): Response
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->with(['category', 'photos'])
            ->firstOrFail();

        $categories = Category::active()
            ->parents()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Dashboard/StoreEdit', [
            'store' => [
                'id' => $store->id,
                'slug' => $store->slug,
                'name' => $store->name,
                'description' => $store->description,
                'sale_type' => $store->sale_type,
                'store_type' => $store->store_type,
                'category_id' => $store->category_id,
                'subcategory' => $store->subcategory,
                'gender' => $store->gender,
                'min_order' => $store->min_order,
                'whatsapp' => $store->whatsapp,
                'phone' => $store->phone,
                'email' => $store->email,
                'website' => $store->website,
                'instagram' => $store->instagram,
                'facebook' => $store->facebook,
                'tiktok' => $store->tiktok,
                'address' => $store->address,
                'address_number' => $store->address_number,
                'address_complement' => $store->address_complement,
                'google_maps_url' => $store->google_maps_url,
                'city' => $store->city,
                'state' => $store->state,
                'zip_code' => $store->zip_code,
                'logo_url' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
                'video_url' => $store->video_url,
                'photos' => $store->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url ?? 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800',
                        'order' => $photo->pivot->order,
                        'is_primary' => $photo->pivot->is_primary,
                    ];
                }),
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified store.
     */
    public function update(Request $request, string $slug): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sale_type' => ['required', 'in:atacado,varejo,ambos'],
            'store_type' => ['required', 'in:fisica,virtual,ambos'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory' => ['nullable', 'array'],
            'subcategory.*' => ['string', 'max:255'],
            'gender' => ['nullable', 'in:masculino,feminino,unissex'],
            'min_order' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:50'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        // Process logo upload if provided
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($store->logo_path && \Storage::disk('public')->exists($store->logo_path)) {
                \Storage::disk('public')->delete($store->logo_path);
            }

            $logo = $request->file('logo');
            $logoPath = $logo->store("stores/store_{$store->id}/logo", 'public');
            $validated['logo_path'] = $logoPath;
        }

        // Remove logo file from validated data (only paths should be saved)
        unset($validated['logo']);

        $store->update($validated);

        return redirect()->route('dashboard.stores.edit', $store->slug)
            ->with('success', 'Loja atualizada com sucesso!');
    }

    /**
     * Display photo management for the store.
     */
    public function photos(string $slug): Response
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->with(['photos', 'collections.media', 'collections.videos', 'plan'])
            ->firstOrFail();

        $maxFeaturedPhotos = $this->getStoreLimit($store, 'photos_per_vitrine', 3);
        $maxCollections = $this->getStoreLimit($store, 'collections_per_vitrine', -1);
        $maxPhotosPerCollection = $this->getStoreLimit($store, 'photos_per_collection', -1);
        $maxVideosPerCollection = $this->getStoreLimit($store, 'videos_per_collection', 0);

        $currentPhotos = $store->photos()
            ->where('type', 'image')
            ->where('media.is_active', true)
            ->whereNull('media.team_collection_id')
            ->where(function ($query) {
                $query->whereNull('category')->orWhere('category', '!=', 'logo');
            })
            ->count();

        $currentCollections = $store->collections->count();

        $featuredPhotos = $store->photos
            ->where('type', 'image')
            ->where('is_active', true)
            ->where('team_collection_id', null)
            ->where('category', '!=', 'logo')
            ->values();

        return Inertia::render('Dashboard/StorePhotos', [
            'store' => [
                'id' => $store->id,
                'slug' => $store->slug,
                'name' => $store->name,
                'featured_photos' => $featuredPhotos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url ?? asset('storage/' . $photo->path),
                        'name' => $photo->name,
                        'size' => $photo->size,
                        'order' => (int) ($photo->pivot?->order ?? 0),
                        'is_primary' => (bool) ($photo->pivot?->is_primary ?? false),
                        'is_active' => (bool) $photo->is_active,
                    ];
                }),
                'collections' => $store->collections->map(function ($collection) use ($maxPhotosPerCollection, $maxVideosPerCollection) {
                    $collectionPhotosCount = $collection->media->count();
                    $collectionVideosCount = $collection->videos->count();
                    $canAddMorePhotos = $this->canAddWithinLimit($collectionPhotosCount, 1, $maxPhotosPerCollection);
                    $canAddMoreVideos = $this->canAddWithinLimit($collectionVideosCount, 1, $maxVideosPerCollection);

                    return [
                        'id' => $collection->id,
                        'name' => $collection->name,
                        'description' => $collection->description,
                        'is_featured' => $collection->is_featured,
                        'photos_count' => $collectionPhotosCount,
                        'videos_count' => $collectionVideosCount,
                        'media_count' => $collectionPhotosCount + $collectionVideosCount,
                        'max_photos' => $maxPhotosPerCollection,
                        'remaining_photos' => $this->getRemainingLimit($collectionPhotosCount, $maxPhotosPerCollection),
                        'can_add_more_photos' => $canAddMorePhotos,
                        'max_videos' => $maxVideosPerCollection,
                        'remaining_videos' => $this->getRemainingLimit($collectionVideosCount, $maxVideosPerCollection),
                        'can_add_more_videos' => $canAddMoreVideos,
                        'cover_url' => $collection->media->first()?->url ?? $collection->videos->first()?->url ?? null,
                        'photos' => $collection->media->map(function ($photo) {
                            return [
                                'id' => $photo->id,
                                'url' => $photo->url ?? asset('storage/' . $photo->path),
                                'name' => $photo->name,
                                'size' => $photo->size,
                                'type' => 'image',
                            ];
                        })->values(),
                        'videos' => $collection->videos->map(function ($video) {
                            return [
                                'id' => $video->id,
                                'url' => $video->url ?? asset('storage/' . $video->path),
                                'name' => $video->name,
                                'size' => $video->size,
                                'type' => 'video',
                            ];
                        })->values(),
                    ];
                })->values(),
                'photos_count' => $currentPhotos,
                'max_featured_photos' => $maxFeaturedPhotos,
                'remaining_featured_photos' => $this->getRemainingLimit($currentPhotos, $maxFeaturedPhotos),
                'max_collections' => $maxCollections,
                'collections_count' => $currentCollections,
                'remaining_collections' => $this->getRemainingLimit($currentCollections, $maxCollections),
                'max_photos_per_collection' => $maxPhotosPerCollection,
                'max_videos_per_collection' => $maxVideosPerCollection,
                'can_upload_more' => $this->canAddWithinLimit($currentPhotos, 1, $maxFeaturedPhotos),
                'can_create_collections' => $this->canAddWithinLimit($currentCollections, 1, $maxCollections),
                'video_url' => $store->video_url,
            ],
        ]);
    }

    /**
     * Display analytics for the store.
     */
    public function analytics(string $slug): Response|RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->with(['leads' => function ($query) {
                $query->orderBy('created_at', 'desc')->take(50);
            }, 'plan'])
            ->firstOrFail();

        // Check if plan has analytics access
        $hasAnalyticsAccess = $store->plan && $store->plan->hasAnalytics();

        if (!$hasAnalyticsAccess) {
            return redirect()->route('dashboard')
                ->with('error', 'Seu plano atual não inclui acesso a analytics. Faça upgrade para visualizar suas métricas.');
        }

        // Get available metrics for this plan
        $availableMetrics = $store->plan->getAnalyticsMetrics();

        return Inertia::render('Dashboard/StoreAnalytics', [
            'store' => [
                'id' => $store->id,
                'slug' => $store->slug,
                'name' => $store->name,
                'views_count' => $store->views_count,
                'whatsapp_clicks' => $store->whatsapp_clicks,
                'website_clicks' => $store->website_clicks,
                'phone_clicks' => $store->phone_clicks,
                'map_clicks' => $store->map_clicks,
                'shares_count' => $store->shares_count,
                'instagram_clicks' => $store->instagram_clicks,
                'facebook_clicks' => $store->facebook_clicks,
                'tiktok_clicks' => $store->tiktok_clicks,
                'featured' => $store->isFeatured(),
            ],
            'availableMetrics' => $availableMetrics,
            'leads' => $store->leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'whatsapp' => $lead->whatsapp,
                    'action' => $lead->action,
                    'created_at' => $lead->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    /**
     * Upload a new photo to the store.
     */
    public function uploadPhoto(Request $request, string $slug): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->with(['plan'])
            ->firstOrFail();

        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
        } elseif ($request->hasFile('photo')) {
            $photos = [$request->file('photo')];
        }

        if (count($photos) === 0) {
            return redirect()->back()->with('error', 'Selecione ao menos uma foto.');
        }

        $maxFeaturedPhotos = $this->getStoreLimit($store, 'photos_per_vitrine', 3);
        $currentFeaturedPhotos = $store->photos()
            ->whereNull('media.team_collection_id')
            ->count();

        if (!$this->canAddWithinLimit($currentFeaturedPhotos, count($photos), $maxFeaturedPhotos)) {
            return redirect()->back()->with(
                'error',
                "Seu plano permite até {$maxFeaturedPhotos} foto(s) de destaque por vitrine."
            );
        }

        DB::transaction(function () use ($store, $photos): void {
            $nextOrder = ((int) $store->photos()
                ->whereNull('media.team_collection_id')
                ->max('team_media.order')) + 1;

            $hasPrimary = $store->photos()
                ->whereNull('media.team_collection_id')
                ->wherePivot('is_primary', true)
                ->exists();

            foreach ($photos as $index => $photo) {
                $path = $photo->store("stores/store_{$store->id}/photos", 'public');

                $media = $store->media()->create([
                    'name' => $photo->getClientOriginalName(),
                    'path' => $path,
                    'type' => 'image',
                    'size' => $photo->getSize() / 1024, // KB
                    'is_generic' => false,
                    'is_active' => true,
                    'category' => 'highlight',
                    'team_collection_id' => null,
                ]);

                $store->photos()->attach($media->id, [
                    'order' => $nextOrder + $index,
                    'is_primary' => !$hasPrimary && $index === 0,
                ]);
            }
        });

        return redirect()->back()->with('success', count($photos) > 1
            ? 'Fotos de destaque adicionadas com sucesso!'
            : 'Foto de destaque adicionada com sucesso!');
    }

    /**
     * Delete a photo from the store.
     */
    public function deletePhoto(string $slug, int $photo): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $media = $store->photos()
            ->whereNull('media.team_collection_id')
            ->where('media.id', $photo)
            ->findOrFail($photo);

        $store->photos()->detach($media->id);
        $media->delete();

        return redirect()->back()->with('success', 'Foto de destaque removida com sucesso!');
    }

    public function setPrimaryPhoto(string $slug, int $photo): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $primaryPhoto = $store->photos()
            ->whereNull('media.team_collection_id')
            ->where('media.id', $photo)
            ->firstOrFail();

        DB::transaction(function () use ($store, $primaryPhoto): void {
            $orderedIds = $store->photos()
                ->whereNull('media.team_collection_id')
                ->where('media.id', '!=', $primaryPhoto->id)
                ->pluck('media.id')
                ->values();

            $store->photos()->updateExistingPivot($primaryPhoto->id, [
                'is_primary' => true,
                'order' => 1,
            ]);

            foreach ($orderedIds as $index => $photoId) {
                $store->photos()->updateExistingPivot((int) $photoId, [
                    'is_primary' => false,
                    'order' => $index + 2,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Foto principal atualizada com sucesso!');
    }

    public function createCollection(Request $request, string $slug): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $maxCollections = $this->getStoreLimit($store, 'collections_per_vitrine', -1);
        $currentCollections = $store->collections()->count();

        if (!$this->canAddWithinLimit($currentCollections, 1, $maxCollections)) {
            return redirect()->back()->with(
                'error',
                "Seu plano permite até {$maxCollections} coleção(ões) por vitrine."
            );
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $sortOrder = (int) $store->collections()->max('sort_order') + 1;

        $store->collections()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        return redirect()->back()->with('success', 'Coleção criada com sucesso!');
    }

    public function updateCollection(Request $request, string $slug, int $collection): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $teamCollection = $store->collections()->findOrFail($collection);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $teamCollection->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Coleção atualizada com sucesso!');
    }

    public function deleteCollection(string $slug, int $collection): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $teamCollection = $store->collections()->findOrFail($collection);
        $collectionMedia = $store->media()
            ->where('team_collection_id', $teamCollection->id)
            ->get();

        foreach ($collectionMedia as $media) {
            if (\Storage::disk('public')->exists($media->path)) {
                \Storage::disk('public')->delete($media->path);
            }
            $media->delete();
        }

        $teamCollection->delete();

        return redirect()->back()->with('success', 'Coleção removida com sucesso!');
    }

    public function setFeaturedCollection(string $slug, int $collection): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $teamCollection = $store->collections()->findOrFail($collection);

        $store->collections()->update(['is_featured' => false]);
        $teamCollection->update(['is_featured' => true]);

        return redirect()->back()->with('success', 'Coleção em destaque atualizada!');
    }

    public function uploadCollectionPhoto(Request $request, string $slug, int $collection): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->with(['plan'])
            ->firstOrFail();

        $teamCollection = $store->collections()->findOrFail($collection);

        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120', // backward compatibility
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,webm,avi|max:102400', // backward compatibility
            'videos' => 'nullable|array',
            'videos.*' => 'file|mimes:mp4,mov,webm,avi|max:102400',
        ]);

        $photos = [];
        $videos = [];

        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
        } elseif ($request->hasFile('photo')) {
            $photos = [$request->file('photo')];
        }

        if ($request->hasFile('videos')) {
            $videos = $request->file('videos');
        } elseif ($request->hasFile('video')) {
            $videos = [$request->file('video')];
        }

        if (count($photos) === 0 && count($videos) === 0) {
            return redirect()->back()->with('error', 'Selecione ao menos uma mídia.');
        }

        $maxPhotosPerCollection = $this->getStoreLimit($store, 'photos_per_collection', -1);
        $maxVideosPerCollection = $this->getStoreLimit($store, 'videos_per_collection', 0);
        $currentCollectionPhotos = $teamCollection->media()->count();
        $currentCollectionVideos = $teamCollection->videos()->count();

        if (count($photos) > 0 && !$this->canAddWithinLimit($currentCollectionPhotos, count($photos), $maxPhotosPerCollection)) {
            return redirect()->back()->with(
                'error',
                "Seu plano permite até {$maxPhotosPerCollection} foto(s) por coleção."
            );
        }

        if (count($videos) > 0 && !$this->canAddWithinLimit($currentCollectionVideos, count($videos), $maxVideosPerCollection)) {
            if ($maxVideosPerCollection === 0) {
                return redirect()->back()->with(
                    'error',
                    'Seu plano atual não inclui vídeos por coleção. Faça upgrade para liberar esse recurso.'
                );
            }

            return redirect()->back()->with(
                'error',
                "Seu plano permite até {$maxVideosPerCollection} vídeo(s) por coleção."
            );
        }

        foreach ($photos as $photo) {
            $path = $photo->store("stores/store_{$store->id}/collections/{$teamCollection->id}", 'public');

            $store->media()->create([
                'team_collection_id' => $teamCollection->id,
                'name' => $photo->getClientOriginalName(),
                'path' => $path,
                'type' => 'image',
                'size' => $photo->getSize() / 1024, // Convert to KB
                'is_generic' => false,
                'is_active' => true,
                'category' => 'collection',
            ]);
        }

        foreach ($videos as $video) {
            $path = $video->store("stores/store_{$store->id}/collections/{$teamCollection->id}", 'public');

            $store->media()->create([
                'team_collection_id' => $teamCollection->id,
                'name' => $video->getClientOriginalName(),
                'path' => $path,
                'type' => 'video',
                'size' => $video->getSize() / 1024, // Convert to KB
                'is_generic' => false,
                'is_active' => true,
                'category' => 'collection',
            ]);
        }

        $uploadedPhotos = count($photos);
        $uploadedVideos = count($videos);
        $uploadedTotal = $uploadedPhotos + $uploadedVideos;

        $successMessage = 'Mídia adicionada na coleção com sucesso!';
        if ($uploadedTotal > 1) {
            $successMessage = 'Mídias adicionadas na coleção com sucesso!';
        } elseif ($uploadedVideos === 1 && $uploadedPhotos === 0) {
            $successMessage = 'Vídeo adicionado na coleção com sucesso!';
        } elseif ($uploadedPhotos === 1 && $uploadedVideos === 0) {
            $successMessage = 'Foto adicionada na coleção com sucesso!';
        }

        return redirect()->back()->with('success', $successMessage);
    }

    public function deleteCollectionPhoto(string $slug, int $collection, int $photo): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $teamCollection = $store->collections()->findOrFail($collection);

        $media = $store->media()
            ->where('team_collection_id', $teamCollection->id)
            ->findOrFail($photo);

        if (\Storage::disk('public')->exists($media->path)) {
            \Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        return redirect()->back()->with('success', 'Mídia da coleção removida com sucesso!');
    }

    /**
     * Update video for the store.
     */
    public function updateVideo(Request $request, string $slug): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'video_url' => ['nullable', 'string', 'max:500'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,webm,avi', 'max:102400'], // 100MB
        ]);

        // Process video upload if provided
        if ($request->hasFile('video')) {
            // Delete old video if exists and is a file (not a URL)
            if ($store->video_url && !filter_var($store->video_url, FILTER_VALIDATE_URL)) {
                if (\Storage::disk('public')->exists($store->video_url)) {
                    \Storage::disk('public')->delete($store->video_url);
                }
            }

            $video = $request->file('video');
            $videoPath = $video->store("stores/store_{$store->id}/videos", 'public');
            $validated['video_url'] = $videoPath;
        }

        // Remove video file from validated data (only paths should be saved)
        unset($validated['video']);

        $store->update($validated);

        return redirect()->back()->with('success', 'Vídeo atualizado com sucesso!');
    }

    /**
     * Delete the saved video for the store.
     */
    public function deleteVideo(string $slug): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($store->video_url && !filter_var($store->video_url, FILTER_VALIDATE_URL)) {
            if (\Storage::disk('public')->exists($store->video_url)) {
                \Storage::disk('public')->delete($store->video_url);
            }
        }

        $store->update(['video_url' => null]);

        return redirect()->back()->with('success', 'Vídeo removido com sucesso!');
    }

    private function getStoreLimit(Team $store, string $resource, int $default): int
    {
        if (!$store->plan) {
            return $default;
        }

        $configuredLimit = $store->plan->limits()
            ->where('module', 'store')
            ->where('resource', $resource)
            ->value('limit_value');

        return $configuredLimit !== null ? (int) $configuredLimit : $default;
    }

    private function canAddWithinLimit(int $current, int $incoming, int $limit): bool
    {
        if ($limit < 0) {
            return true;
        }

        return ($current + $incoming) <= $limit;
    }

    private function getRemainingLimit(int $current, int $limit): ?int
    {
        if ($limit < 0) {
            return null;
        }

        return max(0, $limit - $current);
    }
}
