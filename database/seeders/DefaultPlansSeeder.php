<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class DefaultPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Criar planos
        $this->createFreePlan();
        $this->createVitrinePlan();
        $this->createDestaquePlan();
    }

    private function createFreePlan()
    {
        // Criar plano gratuito no banco
        $plan = Plan::create([
            'name' => 'Gratuito',
            'description' => 'Plano básico para começar',
            'stripe_product_id' => null,
            'stripe_price_id' => null,
            'currency' => 'brl',
            'features' => [
                '1 vitrine ativa',
                'Até 3 fotos',
                'Informações básicas',
                'Aparece em buscas (sem destaque)',
                'Suporte via email',
                'analytics' => [], // Plano gratuito NÃO tem analytics
            ],
            'is_featured' => false,
            'show_on_map' => false, // Plano gratuito NÃO aparece no mapa
            'sort_order' => 999,
            'metadata' => [
                'is_default' => true,
                'cta_text' => 'Plano Atual',
                'highlight_color' => '#718096',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        // Criar limites para o plano gratuito (apenas limites numéricos reais)
        $limits = [
            ['module' => 'store', 'resource' => 'vitrines_count', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 3],
        ];

        foreach ($limits as $limitData) {
            $plan->limits()->create([
                'module' => $limitData['module'],
                'resource' => $limitData['resource'],
                'limit_type' => 'count',
                'limit_value' => $limitData['limit_value'],
                'period' => 'month',
                'is_hard_limit' => true,
                'notification_threshold' => 80,
                'notify_on_limit' => true,
                'grace_period_days' => null,
                'metadata' => [],
            ]);
        }
    }

    private function createVitrinePlan()
    {
        // Criar plano Vitrine
        $plan = Plan::create([
            'name' => 'Vitrine',
            'description' => 'Plano completo para divulgar sua loja',
            'stripe_product_id' => null,
            'currency' => 'brl',
            'features' => [
                'analytics' => [
                    'views',
                    'whatsapp_clicks',
                    'leads',
                ],
                'Até 10 fotos',
                'Galeria completa',
                'Informações detalhadas',
                'Badge de verificado',
                'Analytics básico (visualizações, WhatsApp e leads)',
            ],
            'is_featured' => false,
            'show_on_map' => false, // Plano Vitrine NÃO aparece no mapa (apenas Destaque)
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,

            // Automatic new user discount: 3 months free trial
            'new_user_discount_type' => 'trial',
            'new_user_discount_value' => null,
            'new_user_discount_duration_value' => 3,
            'new_user_discount_duration_unit' => 'months',
        ]);

        // Criar preço para intervalo mensal
        $intervals = [
            ['name' => 'Mensal', 'price' => 49.90, 'interval' => 'month'],
        ];

        foreach ($intervals as $intervalData) {
            // Buscar o intervalo no banco
            $interval = \App\Models\Interval::where('code', 'month')->firstOrFail();

            $plan->intervals()->attach($interval->id, [
                'price' => $intervalData['price'],
                'stripe_price_id' => null,
            ]);
        }

        // Criar limites para o plano Vitrine (apenas limites numéricos reais)
        $limits = [
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 10],
        ];

        foreach ($limits as $limitData) {
            $plan->limits()->create([
                'module' => $limitData['module'],
                'resource' => $limitData['resource'],
                'limit_type' => 'count',
                'limit_value' => $limitData['limit_value'],
                'period' => 'month',
                'is_hard_limit' => true,
                'notification_threshold' => 80,
                'notify_on_limit' => true,
                'metadata' => [],
            ]);
        }
    }

    private function createDestaquePlan()
    {
        // Criar plano Destaque
        $plan = Plan::create([
            'name' => 'Destaque',
            'description' => 'Apareça nos destaques e tenha mais visibilidade',
            'stripe_product_id' => null,
            'currency' => 'brl',
            'features' => [
                'analytics' => [
                    'views',
                    'whatsapp_clicks',
                    'website_clicks',
                    'phone_clicks',
                    'map_clicks',
                    'shares',
                    'leads',
                    'instagram_clicks',
                    'facebook_clicks',
                    'tiktok_clicks',
                ],
                'Todos os recursos do Vitrine',
                '🗺️ Aparece no mapa de anunciantes',
                '⭐ Aparece nos destaques da home',
                'Badge "Destaque" visual',
                'Até 20 fotos',
                'Posicionamento premium nas buscas',
                'Analytics completo (todas as métricas + gráficos)',
            ],
            'is_featured' => true, // Plano em destaque
            'show_on_map' => true, // Plano Destaque APARECE no mapa
            'sort_order' => 2,
            'is_active' => true,
            'is_default' => false,

            // Automatic new user discount: 1 month free trial
            'new_user_discount_type' => 'trial',
            'new_user_discount_value' => null,
            'new_user_discount_duration_value' => 1,
            'new_user_discount_duration_unit' => 'months',
        ]);

        // Criar preço para intervalo mensal
        $intervals = [
            ['name' => 'Mensal', 'price' => 99.90, 'interval' => 'month'],
        ];

        foreach ($intervals as $intervalData) {
            // Buscar o intervalo no banco
            $interval = \App\Models\Interval::where('code', 'month')->firstOrFail();

            $plan->intervals()->attach($interval->id, [
                'price' => $intervalData['price'],
                'stripe_price_id' => null,
            ]);
        }

        // Criar limites para o plano Destaque (apenas limites numéricos reais)
        $limits = [
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 20],
        ];

        foreach ($limits as $limitData) {
            $plan->limits()->create([
                'module' => $limitData['module'],
                'resource' => $limitData['resource'],
                'limit_type' => 'count',
                'limit_value' => $limitData['limit_value'],
                'period' => 'month',
                'is_hard_limit' => true,
                'notification_threshold' => 80,
                'notify_on_limit' => true,
                'metadata' => [],
            ]);
        }
    }


}
