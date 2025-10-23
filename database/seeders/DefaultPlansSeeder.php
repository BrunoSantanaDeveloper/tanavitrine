<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Laravel\Cashier\Cashier;
use Stripe\Stripe;

class DefaultPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Configurar chave do Stripe explicitamente
        Stripe::setApiKey(config('cashier.secret'));

        // Criar produto no Stripe
        $stripe = Cashier::stripe();

        // Criar planos
        $this->createFreePlan($stripe);
        $this->createVitrinePlan($stripe);
        $this->createDestaquePlan($stripe);
        $this->createPremiumPlan($stripe);
    }

    private function createFreePlan($stripe)
    {
        $stripeProduct = $stripe->products->create([
            'name' => 'Plano Gratuito',
            'description' => 'Plano básico gratuito para começar na TanaVitrine',
            'metadata' => [
                'features' => json_encode([
                    '1 vitrine ativa',
                    'Até 3 fotos',
                    'Informações básicas',
                    'Aparece em buscas',
                    'Suporte via email',
                ]),
                'is_default' => true,
            ],
        ]);

        // Criar preço gratuito no Stripe
        $stripePrice = $stripe->prices->create([
            'unit_amount' => 0,
            'currency' => 'brl',
            'product' => $stripeProduct->id,
            'recurring' => [
                'interval' => 'month',
            ],
        ]);

        // Criar plano gratuito no banco
        $plan = Plan::create([
            'name' => 'Gratuito',
            'description' => 'Plano básico para começar',
            'stripe_product_id' => $stripeProduct->id,
            'stripe_price_id' => $stripePrice->id,
            'currency' => 'brl',
            'features' => [
                '1 vitrine ativa',
                'Até 3 fotos',
                'Informações básicas',
                'Aparece em buscas (sem destaque)',
                'Suporte via email',
            ],
            'is_featured' => false,
            'sort_order' => 999,
            'metadata' => [
                'is_default' => true,
                'cta_text' => 'Plano Atual',
                'highlight_color' => '#718096',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        // Criar limites para o plano gratuito
        $limits = [
            ['module' => 'store', 'resource' => 'vitrines_count', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 3],
            ['module' => 'store', 'resource' => 'featured_listing', 'limit_value' => 0],
            ['module' => 'store', 'resource' => 'analytics_access', 'limit_value' => 0],
            ['module' => 'store', 'resource' => 'priority_support', 'limit_value' => 0],
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

    private function createVitrinePlan($stripe)
    {
        // Criar produto Vitrine no Stripe
        $stripeProduct = $stripe->products->create([
            'name' => 'Plano Vitrine',
            'description' => 'Plano completo para divulgar sua loja',
            'metadata' => [
                'plan_type' => 'vitrine',
            ],
        ]);

        // Criar plano Vitrine
        $plan = Plan::create([
            'name' => 'Vitrine',
            'description' => 'Plano completo para divulgar sua loja',
            'stripe_product_id' => $stripeProduct->id,
            'currency' => 'brl',
            'features' => [
                'Até 10 fotos',
                'Galeria completa',
                'Informações detalhadas',
                'Badge de verificado',
                'Analytics básico',
            ],
            'is_featured' => false,
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
        ]);

        // Criar preços para diferentes intervalos
        $intervals = [
            ['name' => 'Mensal', 'price' => 49.90, 'interval' => 'month'],
            ['name' => 'Anual', 'price' => 479.04, 'interval' => 'year'], // 20% off
        ];

        foreach ($intervals as $intervalData) {
            $stripePrice = $stripe->prices->create([
                'unit_amount' => (int)($intervalData['price'] * 100),
                'currency' => 'brl',
                'product' => $stripeProduct->id,
                'recurring' => [
                    'interval' => $intervalData['interval'],
                ],
            ]);

            // Buscar o intervalo no banco
            $interval = \App\Models\Interval::where('code', $intervalData['interval'] === 'year' ? 'year' : 'month')->firstOrFail();

            $plan->intervals()->attach($interval->id, [
                'price' => $intervalData['price'],
                'stripe_price_id' => $stripePrice->id,
            ]);
        }

        // Criar limites para o plano Vitrine
        $limits = [
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 10],
            ['module' => 'store', 'resource' => 'featured_listing', 'limit_value' => 0],
            ['module' => 'store', 'resource' => 'analytics_access', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'verified_badge', 'limit_value' => 1],
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

    private function createDestaquePlan($stripe)
    {
        // Criar produto Destaque no Stripe
        $stripeProduct = $stripe->products->create([
            'name' => 'Plano Destaque',
            'description' => 'Apareça nos destaques e tenha mais visibilidade',
            'metadata' => [
                'plan_type' => 'destaque',
            ],
        ]);

        // Criar plano Destaque
        $plan = Plan::create([
            'name' => 'Destaque',
            'description' => 'Apareça nos destaques e tenha mais visibilidade',
            'stripe_product_id' => $stripeProduct->id,
            'currency' => 'brl',
            'features' => [
                'Todos os recursos do Vitrine',
                '⭐ Aparece nos destaques da home',
                'Badge "Destaque" visual',
                'Até 20 fotos',
                'Posicionamento premium nas buscas',
                'Analytics avançado'
            ],
            'is_featured' => true, // Plano em destaque
            'sort_order' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        // Criar preços para diferentes intervalos
        $intervals = [
            ['name' => 'Mensal', 'price' => 99.90, 'interval' => 'month'],
            ['name' => 'Anual', 'price' => 958.08, 'interval' => 'year'], // 20% off
        ];

        foreach ($intervals as $intervalData) {
            $stripePrice = $stripe->prices->create([
                'unit_amount' => (int)($intervalData['price'] * 100),
                'currency' => 'brl',
                'product' => $stripeProduct->id,
                'recurring' => [
                    'interval' => $intervalData['interval'],
                ],
            ]);

            // Buscar o intervalo no banco
            $interval = \App\Models\Interval::where('code', $intervalData['interval'] === 'year' ? 'year' : 'month')->firstOrFail();

            $plan->intervals()->attach($interval->id, [
                'price' => $intervalData['price'],
                'stripe_price_id' => $stripePrice->id,
            ]);
        }

        // Criar limites para o plano Destaque
        $limits = [
            ['module' => 'store', 'resource' => 'photos_per_vitrine', 'limit_value' => 20],
            ['module' => 'store', 'resource' => 'featured_listing', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'analytics_access', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'verified_badge', 'limit_value' => 1],
            ['module' => 'store', 'resource' => 'premium_position', 'limit_value' => 1],
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
