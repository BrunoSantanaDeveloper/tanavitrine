<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cupom Black VIP - 100% de desconto por 3 meses - MOSTRADO NO EXIT INTENT
        Coupon::firstOrCreate(
            ['code' => 'BLACKVIP'],
            [
                'name' => 'Black VIP',
                'description' => 'Cupom especial VIP com 100% de desconto em todos os planos por 3 meses',
                'type' => 'percentage',
                'value' => 100.00,
                'max_uses' => null, // Usos ilimitados
                'uses_count' => 0,
                'valid_from' => now(), // Válido a partir de agora
                'valid_until' => now()->addMonths(6), // Cupom válido por 6 meses
                'duration_value' => 3, // Duração do benefício (3 meses grátis)
                'duration_unit' => 'months', // Unidade de tempo
                'is_active' => true,
                'is_exit_intent' => true, // Mostrar quando usuário tentar sair
                'metadata' => [
                    'description' => 'Cupom VIP especial - 3 meses grátis',
                    'target' => 'all_plans',
                ],
            ]
        );

        // Você pode adicionar outros cupons aqui
        // Exemplo de cupom com 50% de desconto
        Coupon::firstOrCreate(
            ['code' => 'PROMO50'],
            [
                'name' => 'Promoção 50%',
                'description' => 'Cupom promocional com 50% de desconto',
                'type' => 'percentage',
                'value' => 50.00,
                'max_uses' => 100, // Máximo de 100 usos
                'uses_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addDays(30), // Mantido para compatibilidade
                'duration_value' => 30, // Duração numérica
                'duration_unit' => 'days', // Unidade de tempo
                'is_active' => true,
                'metadata' => [
                    'description' => 'Cupom promocional',
                ],
            ]
        );
    }
}
