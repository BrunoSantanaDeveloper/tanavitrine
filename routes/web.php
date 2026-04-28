<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\OauthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\User\LoginLinkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OnboardingProgressController;
use App\Http\Controllers\SubscriptionSuccessController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\DashboardStoreController;

// Health check endpoint
Route::get('/health', function () {
    return response('OK', 200);
});

// Public storage route (no auth required)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath)) {
        abort(404);
    }

    try {
        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath),
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    } catch (\Exception $e) {
        \Log::error('Error serving storage file', [
            'path' => $path,
            'error' => $e->getMessage(),
        ]);
        abort(500, 'Error serving file');
    }
})->where('path', '.*')->name('storage.local')->withoutMiddleware([
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
]);

// Public routes
Route::get('/', [WelcomeController::class, 'home'])->name('home');
Route::get('/atacado', [WelcomeController::class, 'atacado'])->name('atacado');
Route::get('/varejo', [WelcomeController::class, 'varejo'])->name('varejo');
Route::get('/fabricantes', [WelcomeController::class, 'fabricantes'])->name('fabricantes');
Route::get('/mapa-de-lojas', [WelcomeController::class, 'mapaDeLojas'])->name('mapa-de-lojas');
Route::get('/prices', [WelcomeController::class, 'prices'])->name('prices');
Route::get('/about', [WelcomeController::class, 'about'])->name('about');
Route::post('/track/traffic', [TrafficController::class, 'track'])->name('traffic.track');

if (app()->environment('local')) {
    $previewStores = [
        [
            'id' => 30,
            'code' => 'TV0030',
            'slug' => 'bya-lance-preview',
            'url' => '/dev/preview-anuncios/bya-lance-preview',
            'previewDetailUrl' => '/dev/preview-anuncios/bya-lance-preview',
            'badge' => 'Varejo',
            'name' => 'Bya Lance',
            'category' => 'Roupas',
            'subcategory' => ['Feminino'],
            'description' => 'Loja Bya Lance Somos a Loja Bya Lance, uma marca dedicada a trazer estilo, qualidade e tendências em moda feminina para mulheres que valorizam elegância no dia a dia.',
            'saleType' => 'Varejo',
            'storeType' => 'Virtual',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'city' => 'Goiânia',
            'state' => 'GO',
            'latitude' => null,
            'longitude' => null,
            'whatsapp' => '(62) 99999-0030',
            'logo' => null,
            'image' => '/images/room-vetfun3.png',
            'images' => [
                '/images/room-vetfun3.png',
                '/images/room-vetfun2.png',
                '/images/room-vetfun.jpg',
            ],
            'featured' => false,
            'show_on_map' => false,
            'can_favorite' => false,
            'is_favorited' => false,
        ],
        [
            'id' => 28,
            'code' => 'TV0028',
            'slug' => 'breeze-summer-wear-preview',
            'url' => '/dev/preview-anuncios/breeze-summer-wear-preview',
            'previewDetailUrl' => '/dev/preview-anuncios/breeze-summer-wear-preview',
            'badge' => 'Ambos',
            'name' => 'Breeze - Summer Wear',
            'category' => 'Moda Fitness',
            'subcategory' => ['Feminino', 'Moda Praia'],
            'description' => 'Somos a Breeze Summerwear, uma marca criada para traduzir o clima do verão em peças leves, estilosas e cheias de personalidade. Atendimento para atacado e varejo.',
            'saleType' => 'Ambos',
            'storeType' => 'Ambos',
            'minOrder' => '6',
            'location' => 'Aparecida de Goiânia - GO',
            'city' => 'Aparecida de Goiânia',
            'state' => 'GO',
            'latitude' => null,
            'longitude' => null,
            'whatsapp' => '(62) 98888-0028',
            'logo' => null,
            'image' => '/images/brand-image.png',
            'images' => [
                '/images/brand-image.png',
                '/images/dashboard-light.webp',
                '/images/dashboard-dark.webp',
            ],
            'featured' => false,
            'show_on_map' => false,
            'can_favorite' => false,
            'is_favorited' => false,
        ],
        [
            'id' => 27,
            'code' => 'TV0027',
            'slug' => 'loja-dani-souza-preview',
            'url' => '/dev/preview-anuncios/loja-dani-souza-preview',
            'previewDetailUrl' => '/dev/preview-anuncios/loja-dani-souza-preview',
            'badge' => 'Varejo',
            'name' => 'Loja Dani Souza',
            'category' => 'Calçados',
            'subcategory' => ['Feminino'],
            'description' => 'Loja Dani Souza Somos a Loja Dani Souza, uma loja online especializada em tênis femininos e masculinos das principais marcas do mercado, com foco em conforto e estilo.',
            'saleType' => 'Varejo',
            'storeType' => 'Virtual',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'city' => 'Goiânia',
            'state' => 'GO',
            'latitude' => null,
            'longitude' => null,
            'whatsapp' => '(62) 97777-0027',
            'logo' => null,
            'image' => '/images/notification.png',
            'images' => [
                '/images/notification.png',
                '/images/saving-money.png',
            ],
            'featured' => false,
            'show_on_map' => false,
            'can_favorite' => false,
            'is_favorited' => false,
        ],
        [
            'id' => 26,
            'code' => 'TV0026',
            'slug' => 'gaby-glamour-preview',
            'url' => '/dev/preview-anuncios/gaby-glamour-preview',
            'previewDetailUrl' => '/dev/preview-anuncios/gaby-glamour-preview',
            'badge' => 'Varejo',
            'name' => 'Gaby Glamour',
            'category' => 'Acessórios',
            'subcategory' => ['Feminino'],
            'description' => 'Gaby Glamour Somos a Gaby Glamour, uma loja criada para mulheres que amam estilo, autenticidade e aquele toque especial em bolsas e acessórios.',
            'saleType' => 'Varejo',
            'storeType' => 'Virtual',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'city' => 'Goiânia',
            'state' => 'GO',
            'latitude' => null,
            'longitude' => null,
            'whatsapp' => '(62) 96666-0026',
            'logo' => null,
            'image' => '/images/og.png',
            'images' => [
                '/images/og.png',
                '/images/video-placeholder.png',
            ],
            'featured' => false,
            'show_on_map' => false,
            'can_favorite' => false,
            'is_favorited' => false,
        ],
    ];

    $previewStoreDetails = [
        'bya-lance-preview' => [
            'id' => 30,
            'code' => 'TV0030',
            'slug' => 'bya-lance-preview',
            'name' => 'Bya Lance',
            'description' => 'Loja Bya Lance Somos a Loja Bya Lance, uma marca dedicada a trazer estilo, qualidade e tendências em moda feminina para mulheres que valorizam elegância e praticidade.',
            'badge' => 'Varejo',
            'category' => 'Roupas',
            'subcategory' => ['Feminino'],
            'gender' => 'Feminino',
            'saleType' => 'Varejo',
            'storeType' => 'Online',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'address' => 'Rua Exemplo, 123 - Centro',
            'whatsapp' => '(62) 99999-0030',
            'phone' => '(62) 3333-0030',
            'email' => 'contato@byalance.preview',
            'website' => 'https://example.com',
            'instagram' => 'https://instagram.com',
            'facebook' => null,
            'tiktok' => null,
            'featured' => false,
            'logo' => null,
            'video_url' => null,
            'images' => [
                ['id' => 1, 'url' => '/images/room-vetfun3.png'],
                ['id' => 2, 'url' => '/images/room-vetfun2.png'],
                ['id' => 3, 'url' => '/images/room-vetfun.jpg'],
            ],
            'views_count' => 120,
            'is_favorited' => false,
        ],
        'breeze-summer-wear-preview' => [
            'id' => 28,
            'code' => 'TV0028',
            'slug' => 'breeze-summer-wear-preview',
            'name' => 'Breeze - Summer Wear',
            'description' => 'Somos a Breeze Summerwear, uma marca criada para traduzir o clima do verão em peças leves, estilosas e cheias de personalidade.',
            'badge' => 'Ambos',
            'category' => 'Moda Fitness',
            'subcategory' => ['Feminino', 'Moda Praia'],
            'gender' => 'Feminino',
            'saleType' => 'Ambos',
            'storeType' => 'Fisica e Online',
            'minOrder' => '6',
            'location' => 'Aparecida de Goiânia - GO',
            'address' => 'Av. Exemplo, 456 - Jardim',
            'whatsapp' => '(62) 98888-0028',
            'phone' => '(62) 3222-0028',
            'email' => 'contato@breeze.preview',
            'website' => 'https://example.com',
            'instagram' => 'https://instagram.com',
            'facebook' => 'https://facebook.com',
            'tiktok' => 'https://tiktok.com',
            'featured' => false,
            'logo' => null,
            'video_url' => null,
            'images' => [
                ['id' => 1, 'url' => '/images/brand-image.png'],
                ['id' => 2, 'url' => '/images/dashboard-light.webp'],
                ['id' => 3, 'url' => '/images/dashboard-dark.webp'],
            ],
            'views_count' => 98,
            'is_favorited' => false,
        ],
        'loja-dani-souza-preview' => [
            'id' => 27,
            'code' => 'TV0027',
            'slug' => 'loja-dani-souza-preview',
            'name' => 'Loja Dani Souza',
            'description' => 'Loja online especializada em tenis femininos e masculinos, com curadoria de modelos de alta procura e atendimento via WhatsApp.',
            'badge' => 'Varejo',
            'category' => 'Calçados',
            'subcategory' => ['Feminino'],
            'gender' => 'Unissex',
            'saleType' => 'Varejo',
            'storeType' => 'Online',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'address' => 'Setor Bueno - Goiânia',
            'whatsapp' => '(62) 97777-0027',
            'phone' => '(62) 3111-0027',
            'email' => 'contato@danisouza.preview',
            'website' => null,
            'instagram' => 'https://instagram.com',
            'facebook' => null,
            'tiktok' => null,
            'featured' => false,
            'logo' => null,
            'video_url' => null,
            'images' => [
                ['id' => 1, 'url' => '/images/notification.png'],
                ['id' => 2, 'url' => '/images/saving-money.png'],
            ],
            'views_count' => 62,
            'is_favorited' => false,
        ],
        'gaby-glamour-preview' => [
            'id' => 26,
            'code' => 'TV0026',
            'slug' => 'gaby-glamour-preview',
            'name' => 'Gaby Glamour',
            'description' => 'Acessorios femininos com foco em bolsas e pecas versateis para looks casuais e elegantes.',
            'badge' => 'Varejo',
            'category' => 'Acessórios',
            'subcategory' => ['Feminino'],
            'gender' => 'Feminino',
            'saleType' => 'Varejo',
            'storeType' => 'Online',
            'minOrder' => null,
            'location' => 'Goiânia - GO',
            'address' => 'Goiânia - GO',
            'whatsapp' => '(62) 96666-0026',
            'phone' => '(62) 3000-0026',
            'email' => 'contato@gabyglamour.preview',
            'website' => null,
            'instagram' => 'https://instagram.com',
            'facebook' => null,
            'tiktok' => null,
            'featured' => false,
            'logo' => null,
            'video_url' => null,
            'images' => [
                ['id' => 1, 'url' => '/images/og.png'],
                ['id' => 2, 'url' => '/images/video-placeholder.png'],
            ],
            'views_count' => 74,
            'is_favorited' => false,
        ],
    ];

    $previewStores2 = array_map(function (array $store) {
        $store['url'] = '/dev/preview2-anuncios/' . $store['slug'];
        $store['previewDetailUrl'] = '/dev/preview2-anuncios/' . $store['slug'];
        return $store;
    }, $previewStores);

    Route::get('/dev/preview-anuncios', function () use ($previewStores) {
        return \Inertia\Inertia::render('Varejo', [
            'canLogin' => true,
            'canRegister' => true,
            'stores' => $previewStores,
            'categories' => [
                ['id' => 1, 'name' => 'Roupas', 'slug' => 'roupas'],
                ['id' => 2, 'name' => 'Moda Fitness', 'slug' => 'moda-fitness'],
                ['id' => 3, 'name' => 'Calçados', 'slug' => 'calcados'],
                ['id' => 4, 'name' => 'Acessórios', 'slug' => 'acessorios'],
            ],
            'states' => ['GO', 'SP'],
            'cities' => ['Goiânia', 'Aparecida de Goiânia', 'São Paulo'],
            'seo' => [
                'title' => 'Preview Varejo',
                'description' => 'Preview local da pagina de anuncios',
            ],
        ]);
    })->name('dev.preview-anuncios');

    Route::get('/dev/preview-anuncios/{slug}', function (string $slug) use ($previewStoreDetails) {
        abort_unless(isset($previewStoreDetails[$slug]), 404);

        return \Inertia\Inertia::render('StoreDetail', [
            'store' => $previewStoreDetails[$slug],
            'canLogin' => true,
            'canRegister' => true,
        ]);
    })->name('dev.preview-anuncios.show');

    Route::get('/dev/preview2-anuncios', function () use ($previewStores2) {
        return \Inertia\Inertia::render('Dev/VarejoPreview2', [
            'canLogin' => true,
            'canRegister' => true,
            'stores' => $previewStores2,
            'categories' => [
                ['id' => 1, 'name' => 'Roupas', 'slug' => 'roupas'],
                ['id' => 2, 'name' => 'Moda Fitness', 'slug' => 'moda-fitness'],
                ['id' => 3, 'name' => 'Calçados', 'slug' => 'calcados'],
                ['id' => 4, 'name' => 'Acessórios', 'slug' => 'acessorios'],
            ],
            'states' => ['GO', 'SP'],
            'cities' => ['Goiânia', 'Aparecida de Goiânia', 'São Paulo'],
            'seo' => [
                'title' => 'Preview2 Varejo',
                'description' => 'Preview local para testes de edicao',
            ],
        ]);
    })->name('dev.preview2-anuncios');

    Route::get('/dev/preview2-anuncios/{slug}', function (string $slug) use ($previewStoreDetails) {
        abort_unless(isset($previewStoreDetails[$slug]), 404);

        return \Inertia\Inertia::render('Dev/StoreDetailPreview2', [
            'store' => $previewStoreDetails[$slug],
            'canLogin' => true,
            'canRegister' => true,
        ]);
    })->name('dev.preview2-anuncios.show');
}

// API route for infinite scroll
Route::get('/api/stores/load-more', [WelcomeController::class, 'loadMoreStores'])->name('api.stores.load-more');

// Store routes
Route::get('/loja/{slug}', [StoreController::class, 'show'])->name('store.show');
Route::post('/loja/{slug}/lead', [StoreController::class, 'captureLead'])->name('store.lead');
Route::post('/loja/{slug}/track/whatsapp', [StoreController::class, 'trackWhatsAppClick'])->name('store.track.whatsapp');
Route::post('/loja/{slug}/track/website', [StoreController::class, 'trackWebsiteClick'])->name('store.track.website');
Route::post('/loja/{slug}/track/phone', [StoreController::class, 'trackPhoneClick'])->name('store.track.phone');
Route::post('/loja/{slug}/track/map', [StoreController::class, 'trackMapClick'])->name('store.track.map');
Route::post('/loja/{slug}/track/instagram', [StoreController::class, 'trackInstagramClick'])->name('store.track.instagram');
Route::post('/loja/{slug}/track/facebook', [StoreController::class, 'trackFacebookClick'])->name('store.track.facebook');
Route::post('/loja/{slug}/track/tiktok', [StoreController::class, 'trackTikTokClick'])->name('store.track.tiktok');
Route::post('/loja/{slug}/track/share', [StoreController::class, 'trackShare'])->name('store.track.share');

// Favorite routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::post('/loja/{slug}/favorite', [StoreController::class, 'toggleFavorite'])->name('store.favorite');
    Route::get('/favoritos', [StoreController::class, 'favorites'])->name('favorites');
});

// Category routes
Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categoria/{slug}', [CategoryController::class, 'show'])->name('categories.show');


Route::prefix('auth')->group(
    function () {
        // OAuth
        Route::get('/redirect/{provider}', [OauthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('/callback/{provider}', [OauthController::class, 'callback'])->name('oauth.callback');
        // Magic Link
        Route::middleware('throttle:login-link')->group(function () {
            Route::post('/login-link', [LoginLinkController::class, 'store'])->name('login-link.store');
            Route::get('/login-link/{token}', [LoginLinkController::class, 'login'])
                ->name('login-link.login')
                ->middleware('signed');
        });
    }
);

// Onboarding routes (guest)
Route::middleware('guest')->group(function () {
    Route::get('/onboarding/start', [OnboardingController::class, 'start'])->name('onboarding.start');
});

// Routes that don't require email verification (for subscription and onboarding handling)
Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    Route::get('/subscriptions/success', [SubscriptionController::class, 'success'])->name('subscriptions.success');
    Route::get('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Onboarding success and delivery address
    Route::get('/onboarding/success', [SubscriptionSuccessController::class, 'show'])->name('onboarding.success');
    Route::post('/onboarding/delivery-address', [SubscriptionSuccessController::class, 'saveDeliveryAddress'])->name('onboarding.delivery.save');

    // Onboarding progress saving
    Route::post('/onboarding/progress', [OnboardingProgressController::class, 'save'])->name('onboarding.progress.save');
    Route::post('/onboarding/upload-photo', [OnboardingProgressController::class, 'uploadPhoto'])->name('onboarding.upload.photo');
    Route::post('/onboarding/upload-video', [OnboardingProgressController::class, 'uploadVideo'])->name('onboarding.upload.video');
    Route::delete('/onboarding/media/{mediaId}', [OnboardingProgressController::class, 'deleteMedia'])->name('onboarding.media.delete');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::delete('/auth/destroy/{provider}', [OauthController::class, 'destroy'])->name('oauth.destroy');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    // Dashboard Store Management
    Route::prefix('dashboard/stores')->name('dashboard.stores.')->group(function () {
        Route::get('/', [DashboardStoreController::class, 'index'])->name('index');
        Route::get('/create', [DashboardStoreController::class, 'create'])->name('create');
        Route::post('/', [DashboardStoreController::class, 'store'])->name('store');
        Route::get('/{slug}/edit', [DashboardStoreController::class, 'edit'])->name('edit');
        Route::put('/{slug}', [DashboardStoreController::class, 'update'])->name('update');
        Route::get('/{slug}/photos', [DashboardStoreController::class, 'photos'])->name('photos');
        Route::post('/{slug}/photos', [DashboardStoreController::class, 'uploadPhoto'])->name('photos.upload');
        Route::delete('/{slug}/photos/{photo}', [DashboardStoreController::class, 'deletePhoto'])->name('photos.delete');
        Route::put('/{slug}/photos/{photo}/primary', [DashboardStoreController::class, 'setPrimaryPhoto'])->name('photos.primary');
        Route::post('/{slug}/collections', [DashboardStoreController::class, 'createCollection'])->name('collections.create');
        Route::put('/{slug}/collections/{collection}', [DashboardStoreController::class, 'updateCollection'])->name('collections.update');
        Route::delete('/{slug}/collections/{collection}', [DashboardStoreController::class, 'deleteCollection'])->name('collections.delete');
        Route::put('/{slug}/collections/{collection}/featured', [DashboardStoreController::class, 'setFeaturedCollection'])->name('collections.featured');
        Route::post('/{slug}/collections/{collection}/photos', [DashboardStoreController::class, 'uploadCollectionPhoto'])->name('collections.photos.upload');
        Route::delete('/{slug}/collections/{collection}/photos/{photo}', [DashboardStoreController::class, 'deleteCollectionPhoto'])->name('collections.photos.delete');
        Route::put('/{slug}/video', [DashboardStoreController::class, 'updateVideo'])->name('video.update');
        Route::delete('/{slug}/video', [DashboardStoreController::class, 'deleteVideo'])->name('video.delete');
        Route::get('/{slug}/analytics', [DashboardStoreController::class, 'analytics'])->name('analytics');
    });

    // Favorites
    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/', [FavoriteController::class, 'index'])->name('index');
        Route::post('/', [FavoriteController::class, 'store'])->name('store');
        Route::delete('/{slug}', [FavoriteController::class, 'destroy'])->name('destroy');
        Route::get('/{slug}/check', [FavoriteController::class, 'check'])->name('check');
    });

    Route::post('/user/complete-onboarding', function () {
        $user = auth()->user();
        $user->update(['onboarding_completed' => true]);
        return back();
    })->name('user.complete-onboarding');

    Route::resource('/subscriptions', SubscriptionController::class)
        ->names('subscriptions')
        ->only(['index', 'create', 'store', 'show']);

    // Custom subscription checkout route
    Route::post('/subscriptions/checkout/{plan_interval_id}', [SubscriptionController::class, 'checkout'])
        ->name('subscriptions.checkout');

    // Active subscription plan migration (upgrade / downgrade) without new checkout.
    Route::post('/subscriptions/change-plan/{plan_interval_id}', [SubscriptionController::class, 'changePlan'])
        ->name('subscriptions.change-plan');

    // Formalized subscription management (cancel/reactivate without leaving panel).
    Route::post('/subscriptions/cancel-plan', [SubscriptionController::class, 'cancelPlan'])
        ->name('subscriptions.cancel-plan');
    Route::post('/subscriptions/resume-plan', [SubscriptionController::class, 'resumePlan'])
        ->name('subscriptions.resume-plan');

    // Debug route to check all registered routes
    Route::get('/debug-routes', function () {
        return response()->json([
            'routes' => app('router')->getRoutes()->getRoutesByName(),
        ]);
    });

});
