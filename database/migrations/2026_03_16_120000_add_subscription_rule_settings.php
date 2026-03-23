<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'subscription',
                'key' => 'subscription_default_trial_days',
                'value' => json_encode(14),
                'type' => 'number',
                'is_public' => false,
                'description' => 'Dias de teste padrão para novos cadastros',
                'sort_order' => 1,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_hide_store_when_expired',
                'value' => json_encode(true),
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Retirar vitrine do ar quando assinatura expirar',
                'sort_order' => 2,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_keep_panel_access_when_expired',
                'value' => json_encode(true),
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Permitir acesso ao painel quando assinatura expirar',
                'sort_order' => 3,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_allow_plan_new_user_discounts',
                'value' => json_encode(false),
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Permitir regras automáticas de desconto por plano (legado)',
                'sort_order' => 4,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'subscription_default_trial_days',
                'subscription_hide_store_when_expired',
                'subscription_keep_panel_access_when_expired',
                'subscription_allow_plan_new_user_discounts',
            ])
            ->delete();
    }
};

