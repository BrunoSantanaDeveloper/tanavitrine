<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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
            'subcategory' => ['required', 'string', 'max:255'],
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
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
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
                    'max_photos' => $store->plan->metadata['max_photos'] ?? 3,
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
                'city' => $store->city,
                'state' => $store->state,
                'zip_code' => $store->zip_code,
                'logo_url' => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
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
            'subcategory' => ['nullable', 'string', 'max:255'],
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

        // Remove logo file from validated data (only logo_path should be saved)
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
            ->with(['media', 'plan'])
            ->firstOrFail();

        // Get plan limits
        $maxPhotos = $store->plan ? $store->plan->getModuleLimit('store', 'photos_per_vitrine') : 3;
        $currentPhotos = $store->media()->count();

        return Inertia::render('Dashboard/StorePhotos', [
            'store' => [
                'id' => $store->id,
                'slug' => $store->slug,
                'name' => $store->name,
                'photos' => $store->media->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => $photo->url ?? asset('storage/' . $photo->path),
                        'name' => $photo->name,
                        'size' => $photo->size,
                    ];
                }),
                'photos_count' => $currentPhotos,
                'max_photos' => $maxPhotos,
                'can_upload_more' => $currentPhotos < $maxPhotos,
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
        $hasAnalyticsAccess = $store->plan && $store->plan->getModuleLimit('store', 'analytics_access') > 0;

        if (!$hasAnalyticsAccess) {
            return redirect()->route('dashboard')
                ->with('error', 'Seu plano atual não inclui acesso a analytics. Faça upgrade para visualizar suas métricas.');
        }

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
                'featured' => $store->isFeatured(),
            ],
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

        // Check photo limit from plan
        $maxPhotos = $store->plan ? $store->plan->getModuleLimit('store', 'photos_per_vitrine') : 3;
        $currentPhotos = $store->media()->count();

        if ($currentPhotos >= $maxPhotos) {
            return redirect()->back()->with('error', "Você atingiu o limite de {$maxPhotos} fotos do seu plano. Faça upgrade para adicionar mais fotos.");
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB
        ]);

        $photo = $request->file('photo');
        $path = $photo->store("stores/store_{$store->id}/photos", 'public');

        $store->media()->create([
            'name' => $photo->getClientOriginalName(),
            'path' => $path,
            'type' => 'image',
            'size' => $photo->getSize() / 1024, // Convert to KB
            'is_generic' => false,
            'category' => 'product',
        ]);

        return redirect()->back()->with('success', 'Foto adicionada com sucesso!');
    }

    /**
     * Delete a photo from the store.
     */
    public function deletePhoto(string $slug, int $photo): RedirectResponse
    {
        $store = Team::where('slug', $slug)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $media = $store->media()->findOrFail($photo);

        // Delete file from storage
        if (\Storage::disk('public')->exists($media->path)) {
            \Storage::disk('public')->delete($media->path);
        }

        // Delete record from database
        $media->delete();

        return redirect()->back()->with('success', 'Foto removida com sucesso!');
    }
}
