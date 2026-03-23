<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Services\SubscriptionAccessRuleService;
use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\File;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Get all translation files from the current locale directory.
     */
    private function getAllTranslations(): array
    {
        $locale = Lang::locale();
        $path = lang_path($locale);

        if (!File::isDirectory($path)) {
            return [];
        }

        $translations = [];
        $files = File::files($path);

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();
            // Skip filament translations or any other you want to exclude
            if (str_starts_with($filename, 'filament')) {
                continue;
            }
            $translations[$filename] = Lang::get($filename);
        }

        return $translations;
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentStore = null;
        $subscriptionNav = null;

        if ($user) {
            // Get user's store (only 1 allowed per user)
            $store = \App\Models\Team::where('user_id', $user->id)
                ->where('personal_team', false)
                ->with('plan:id,name')
                ->first();

            if ($store) {
                $currentStore = [
                    'id' => $store->id,
                    'slug' => $store->slug,
                    'name' => $store->name,
                ];
            }

            /** @var Subscription|null $subscription */
            $subscription = $user->subscriptions()
                ->where('name', 'default')
                ->latest()
                ->first();

            if ($subscription) {
                $meta = app(SubscriptionAccessRuleService::class)->buildSubscriptionMeta($subscription);

                $subscriptionNav = [
                    'has_subscription' => true,
                    'plan_name' => $store?->plan?->name ?? $subscription->type,
                    'has_active_access' => (bool) ($meta['has_active_access'] ?? false),
                    'is_trial' => (bool) ($meta['is_trial'] ?? false),
                    'trial_days_remaining' => $meta['trial_days_remaining'],
                    'is_formalized' => (bool) ($meta['is_formalized'] ?? false),
                    'has_active_discount' => (bool) ($meta['has_active_discount'] ?? false),
                    'discount_days_remaining' => $meta['discount_days_remaining'],
                ];
            }
        }

        /** @var array<string, mixed> */
        return array_merge(parent::share($request), [
            'name' => Config::get('app.name', 'Tá na Vitrine'),
            'currentStore' => $currentStore,
            'subscriptionNav' => $subscriptionNav,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'translations' => $this->getAllTranslations(),
        ]);
    }
}
