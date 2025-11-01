<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Validate coupon code
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'plan_price' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($request->code));
        $planPrice = (float) $request->plan_price;

        // Find coupon
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Cupom não encontrado.',
            ], 404);
        }

        // Check if coupon is valid
        if (!$coupon->isValid()) {
            $message = 'Cupom inválido ou expirado.';

            if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
                $message = 'Este cupom já atingiu o limite de usos.';
            } elseif ($coupon->valid_until && now()->isAfter($coupon->valid_until)) {
                $message = 'Este cupom expirou.';
            } elseif ($coupon->valid_from && now()->isBefore($coupon->valid_from)) {
                $message = 'Este cupom ainda não está ativo.';
            }

            return response()->json([
                'valid' => false,
                'message' => $message,
            ], 422);
        }

        // Calculate discount
        $discount = $coupon->calculateDiscount($planPrice);
        $finalPrice = max(0, $planPrice - $discount);
        $discountPercentage = $planPrice > 0 ? ($discount / $planPrice) * 100 : 0;

        // Check if it's a 100% discount (special coupon)
        $isSpecial = $discountPercentage >= 100;

        // Get duration from database fields
        $durationValue = $coupon->duration_value;
        $durationUnit = $coupon->duration_unit;

        // Convert duration to months for display
        $durationMonths = null;
        if ($durationValue && $durationUnit) {
            $durationMonths = match($durationUnit) {
                'days' => ceil($durationValue / 30),
                'months' => $durationValue,
                'years' => $durationValue * 12,
                default => null,
            };
        }

        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount_amount' => round($discount, 2),
                'final_price' => round($finalPrice, 2),
                'discount_percentage' => round($discountPercentage, 2),
                'is_special' => $isSpecial,
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'duration_months' => $durationMonths,
                'valid_until' => $coupon->valid_until?->format('Y-m-d H:i:s'),
            ],
            'message' => $isSpecial
                ? 'Parabéns! Você ganhou acesso especial com 100% de desconto!'
                : 'Cupom aplicado com sucesso!',
        ]);
    }

    /**
     * Get exit intent coupon
     */
    public function exitIntent(Request $request): JsonResponse
    {
        $request->validate([
            'plan_price' => 'required|numeric|min:0',
        ]);

        $planPrice = (float) $request->plan_price;

        // Find exit intent coupon
        $coupon = Coupon::exitIntent()->valid()->first();

        if (!$coupon) {
            return response()->json([
                'found' => false,
                'message' => 'Nenhum cupom especial disponível.',
            ], 404);
        }

        // Calculate discount
        $discount = $coupon->calculateDiscount($planPrice);
        $finalPrice = max(0, $planPrice - $discount);
        $discountPercentage = $planPrice > 0 ? ($discount / $planPrice) * 100 : 0;

        // Check if it's a 100% discount (special coupon)
        $isSpecial = $discountPercentage >= 100;

        // Get duration from database fields
        $durationValue = $coupon->duration_value;
        $durationUnit = $coupon->duration_unit;

        // Convert duration to months for display
        $durationMonths = null;
        if ($durationValue && $durationUnit) {
            $durationMonths = match($durationUnit) {
                'days' => ceil($durationValue / 30),
                'months' => $durationValue,
                'years' => $durationValue * 12,
                default => null,
            };
        }

        return response()->json([
            'found' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount_amount' => round($discount, 2),
                'final_price' => round($finalPrice, 2),
                'discount_percentage' => round($discountPercentage, 2),
                'is_special' => $isSpecial,
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'duration_months' => $durationMonths,
                'valid_until' => $coupon->valid_until?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
