<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STRIPE_PRICE_VITRINE_MONTH = 'price_1T8aJkA0FTb4ruyV9i4VnKsx';
    private const STRIPE_PRICE_DESTAQUE_MONTH = 'price_1T8aKcA0FTb4ruyVFzWw08Tb';

    public function up(): void
    {
        $monthIntervalId = DB::table('intervals')->where('code', 'month')->value('id');
        if (!$monthIntervalId) {
            return;
        }

        $vitrinePlanId = DB::table('plans')->where('name', 'Vitrine')->value('id');
        if ($vitrinePlanId) {
            DB::table('plans')
                ->where('id', $vitrinePlanId)
                ->update([
                    'stripe_price_id' => self::STRIPE_PRICE_VITRINE_MONTH,
                    'updated_at' => now(),
                ]);

            DB::table('plan_intervals')
                ->where('plan_id', $vitrinePlanId)
                ->where('interval_id', $monthIntervalId)
                ->update([
                    'stripe_price_id' => self::STRIPE_PRICE_VITRINE_MONTH,
                    'updated_at' => now(),
                ]);
        }

        $destaquePlanId = DB::table('plans')->where('name', 'Destaque')->value('id');
        if ($destaquePlanId) {
            DB::table('plans')
                ->where('id', $destaquePlanId)
                ->update([
                    'stripe_price_id' => self::STRIPE_PRICE_DESTAQUE_MONTH,
                    'updated_at' => now(),
                ]);

            DB::table('plan_intervals')
                ->where('plan_id', $destaquePlanId)
                ->where('interval_id', $monthIntervalId)
                ->update([
                    'stripe_price_id' => self::STRIPE_PRICE_DESTAQUE_MONTH,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // No-op: evitamos apagar IDs válidos já aplicados em ambiente produtivo.
    }
};

