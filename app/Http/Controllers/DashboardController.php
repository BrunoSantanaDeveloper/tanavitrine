<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        // Buscar a vitrine do usuário (apenas 1)
        $userStore = Team::where('user_id', $user->id)
            ->where('personal_team', false)
            ->with('category')
            ->first();

        // Buscar dados do plano e assinatura (ANTES de verificar se tem store)
        $planData = $this->getPlanData($user);

        // Buscar welcome discount
        $welcomeDiscount = $this->getWelcomeDiscount();

        // Se não tem vitrine, retornar early mas COM informação de subscription
        if (!$userStore) {
            return Inertia::render('Dashboard', [
                'store' => null,
                'stats' => [
                    'total_views' => 0,
                    'whatsapp_clicks' => 0,
                    'website_clicks' => 0,
                    'favorites_received' => 0,
                    'views_trend' => 0,
                    'clicks_trend' => 0,
                    'favorites_trend' => 0,
                ],
                'plan' => $planData,
                'welcomeDiscount' => $welcomeDiscount,
            ]);
        }

        // Calcular estatísticas da vitrine
        $stats = [
            'total_views' => $userStore->views_count ?? 0,
            'whatsapp_clicks' => $userStore->whatsapp_clicks ?? 0,
            'website_clicks' => $userStore->website_clicks ?? 0,
            'favorites_received' => $userStore->favoritedBy()->count(),
            'views_trend' => 0,
            'clicks_trend' => 0,
            'favorites_trend' => 0,
        ];

        // Transformar vitrine para o frontend
        $store = [
            'id' => $userStore->id,
            'slug' => $userStore->slug,
            'name' => $userStore->name,
            'description' => $userStore->description,
            'category' => $userStore->category?->name,
            'subcategory' => $userStore->subcategory,
            'sale_type' => ucfirst($userStore->sale_type),
            'status' => $userStore->status,
            'views_count' => $userStore->views_count ?? 0,
            'whatsapp_clicks' => $userStore->whatsapp_clicks ?? 0,
            'website_clicks' => $userStore->website_clicks ?? 0,
            'favorites_count' => $userStore->favoritedBy()->count(),
            'photos_count' => $userStore->media()->count(),
        ];

        return Inertia::render('Dashboard', [
            'store' => $store,
            'stats' => $stats,
            'plan' => $planData,
            'welcomeDiscount' => $welcomeDiscount,
        ]);
    }

    /**
     * Get plan data with subscription information
     */
    private function getPlanData($user): array
    {
        $planData = [
            'name' => 'Gratuito',
            'max_photos' => 3,
            'features' => [],
            'subscription' => null,
        ];

        // Buscar subscription ativa do usuário (independente do team)
        $subscription = $user->subscriptions()
            ->where('stripe_status', 'active')
            ->latest()
            ->first();

        // Se tem subscription, buscar o plano correspondente
        if ($subscription) {
            $subscriptionItem = $subscription->items->first();
            $plan = null;

            // Tentar encontrar plano pelo stripe_product ou stripe_price
            if ($subscriptionItem) {
                $plan = \App\Models\Plan::where('stripe_product_id', $subscriptionItem->stripe_product)
                    ->orWhere(function ($query) use ($subscriptionItem) {
                        $query->whereHas('intervals', function ($q) use ($subscriptionItem) {
                            $q->where('stripe_price_id', $subscriptionItem->stripe_price);
                        });
                    })
                    ->first();
            }

            // Fallback: usar plano do currentTeam
            if (!$plan && $user->currentTeam && $user->currentTeam->plan) {
                $plan = $user->currentTeam->plan;
            }

            if ($plan) {
                $isTrial = $subscription->onTrial();
                $trialEndsAt = $subscription->trial_ends_at;

                $planData = [
                    'name' => $plan->name,
                    'max_photos' => $plan->getModuleLimit('store', 'photos_per_vitrine'),
                    'features' => $plan->features ?? [],
                    'subscription' => [
                        'status' => $subscription->stripe_status,
                        'is_trial' => $isTrial,
                        'trial_ends_at' => $trialEndsAt ? $trialEndsAt->format('d/m/Y') : null,
                        'trial_days_remaining' => $isTrial && $trialEndsAt ? (int) now()->diffInDays($trialEndsAt, false) : null,
                        'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : null,
                        'is_active' => $subscription->active(),
                        'on_grace_period' => $subscription->onGracePeriod(),
                    ],
                ];
            }
        } elseif ($user->currentTeam && $user->currentTeam->plan) {
            // Fallback: usar plano do currentTeam se não tiver subscription
            $plan = $user->currentTeam->plan;
            $planData = [
                'name' => $plan->name,
                'max_photos' => $plan->getModuleLimit('store', 'photos_per_vitrine'),
                'features' => $plan->features ?? [],
                'subscription' => null,
            ];
        }

        return $planData;
    }

    /**
     * Get welcome discount from session
     */
    private function getWelcomeDiscount(): ?array
    {
        if (session('show_welcome_discount')) {
            $welcomeDiscount = [
                'type' => session('welcome_discount_type'),
                'text' => session('welcome_discount_text'),
                'plan_name' => session('welcome_plan_name'),
            ];

            // Clear session after reading
            session()->forget(['show_welcome_discount', 'welcome_discount_type', 'welcome_discount_text', 'welcome_plan_name']);

            return $welcomeDiscount;
        }

        return null;
    }
}
