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

        // Verificar se precisa fazer onboarding
        if (!$user->onboarding_completed) {
            return Inertia::render('OnBoarding', [
                'categories' => Category::active()
                    ->parents()
                    ->with('children')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'children' => $category->children->map(function ($child) {
                                return [
                                    'id' => $child->id,
                                    'name' => $child->name,
                                    'slug' => $child->slug,
                                ];
                            }),
                        ];
                    }),
            ]);
        }

        // Buscar a vitrine do usuário (apenas 1)
        $userStore = Team::where('user_id', $user->id)
            ->where('personal_team', false)
            ->with('category')
            ->first();

        // Se não tem vitrine, redirecionar para onboarding
        if (!$userStore) {
            return Inertia::render('OnBoarding', [
                'categories' => Category::active()
                    ->parents()
                    ->with('children')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'children' => $category->children->map(function ($child) {
                                return [
                                    'id' => $child->id,
                                    'name' => $child->name,
                                    'slug' => $child->slug,
                                ];
                            }),
                        ];
                    }),
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

        // Buscar dados do plano
        $currentTeam = $user->currentTeam;
        $planData = [
            'name' => 'Gratuito',
            'max_photos' => 3,
            'features' => [],
        ];

        if ($currentTeam && $currentTeam->plan) {
            $planData = [
                'name' => $currentTeam->plan->name,
                'max_photos' => $currentTeam->plan->metadata['max_photos'] ?? 3,
                'features' => $currentTeam->plan->features ?? [],
            ];
        }

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
        ]);
    }
}
