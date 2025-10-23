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
Route::get('/prices', [WelcomeController::class, 'prices'])->name('prices');
Route::get('/about', [WelcomeController::class, 'about'])->name('about');

// Store routes
Route::get('/loja/{slug}', [StoreController::class, 'show'])->name('store.show');
Route::post('/loja/{slug}/lead', [StoreController::class, 'captureLead'])->name('store.lead');
Route::post('/loja/{slug}/track/whatsapp', [StoreController::class, 'trackWhatsAppClick'])->name('store.track.whatsapp');
Route::post('/loja/{slug}/track/website', [StoreController::class, 'trackWebsiteClick'])->name('store.track.website');
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

    // Debug route to check all registered routes
    Route::get('/debug-routes', function () {
        return response()->json([
            'routes' => app('router')->getRoutes()->getRoutesByName(),
        ]);
    });

});
