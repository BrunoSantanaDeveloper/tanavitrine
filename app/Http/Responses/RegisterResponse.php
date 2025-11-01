<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Models\Plan;
use App\Models\PlanInterval;
use App\Models\Coupon;
use Inertia\Inertia;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response|RedirectResponse
    {
        $user = auth()->user();

        // Check if plan_interval is provided in the request
        if ($request->has('plan')) {
            $planIntervalId = $request->input('plan');

            $planInterval = PlanInterval::find($planIntervalId);

            if (!$planInterval) {
                return redirect()->route('dashboard')->with('error', 'Plano inválido');
            }

            $planName = $planInterval->plan->name . ' ' . $planInterval->interval->name;

            // Check if coupon was applied
            $coupon = null;
            $couponCode = session('applied_coupon');
            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper($couponCode))->first();

                if ($coupon && $coupon->isValid()) {
                    \Log::info('Cupom encontrado e válido', [
                        'user_id' => $user->id,
                        'coupon_code' => $coupon->code,
                        'discount' => $coupon->value,
                        'type' => $coupon->type,
                    ]);
                } else {
                    \Log::warning('Cupom inválido ou expirado', [
                        'user_id' => $user->id,
                        'coupon_code' => $couponCode,
                    ]);
                    $coupon = null;
                }
            }

            // If no coupon, check if plan has automatic new user discount
            $applyPlanDiscount = false;
            if (!$coupon && $planInterval->plan->hasNewUserDiscount()) {
                \Log::info('Aplicando desconto automático do plano', [
                    'user_id' => $user->id,
                    'plan' => $planInterval->plan->name,
                    'discount_type' => $planInterval->plan->new_user_discount_type,
                    'discount_value' => $planInterval->plan->new_user_discount_value,
                ]);
                $applyPlanDiscount = true;
            }

            // Calculate discount and pricing
            $originalPrice = (float) $planInterval->price;
            $discountAmount = 0;
            $finalPrice = $originalPrice;
            $isFreeCoupon = false;
            $trialDays = null;
            $discountEndsAt = null;
            $discountSource = null; // 'coupon' or 'plan'

            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($originalPrice);
                $finalPrice = max(0, $originalPrice - $discountAmount);
                $discountSource = 'coupon';

                // Check if coupon is 100% discount (special free access)
                $isFreeCoupon = $coupon->type === 'percentage' && $coupon->value >= 100;

                if ($isFreeCoupon) {
                    // Calculate trial days from coupon duration
                    $durationValue = $coupon->duration_value ?? 1;
                    $durationUnit = $coupon->duration_unit ?? 'months';

                    // Convert to days
                    $trialDays = match($durationUnit) {
                        'days' => $durationValue,
                        'months' => $durationValue * 30,
                        'years' => $durationValue * 365,
                        default => 30,
                    };

                    $discountEndsAt = now()->addDays($trialDays);

                    \Log::info('Cupom de 100% detectado - criando assinatura com período grátis', [
                        'user_id' => $user->id,
                        'coupon_code' => $coupon->code,
                        'duration_value' => $durationValue,
                        'duration_unit' => $durationUnit,
                        'trial_days' => $trialDays,
                        'original_price' => $originalPrice,
                        'discount_amount' => $discountAmount,
                        'final_price' => $finalPrice,
                    ]);
                } else {
                    // Regular coupon with discount
                    \Log::info('Cupom aplicado com desconto parcial', [
                        'user_id' => $user->id,
                        'coupon_code' => $coupon->code,
                        'original_price' => $originalPrice,
                        'discount_amount' => $discountAmount,
                        'final_price' => $finalPrice,
                    ]);
                }
            } elseif ($applyPlanDiscount) {
                // Apply plan automatic discount
                $plan = $planInterval->plan;
                $discountSource = 'plan';

                if ($plan->new_user_discount_type === 'trial') {
                    // Trial period
                    $trialDays = $plan->getNewUserTrialDays();
                    $discountEndsAt = $trialDays ? now()->addDays($trialDays) : null;
                    $isFreeCoupon = true; // Treat trial as free period
                } elseif ($plan->new_user_discount_type === 'percentage') {
                    // Percentage discount
                    $discountAmount = $originalPrice * ($plan->new_user_discount_value / 100);
                    $finalPrice = max(0, $originalPrice - $discountAmount);

                    // Calculate discount end date
                    if ($plan->new_user_discount_duration_value && $plan->new_user_discount_duration_unit) {
                        $discountEndsAt = match($plan->new_user_discount_duration_unit) {
                            'days' => now()->addDays($plan->new_user_discount_duration_value),
                            'months' => now()->addMonths($plan->new_user_discount_duration_value),
                            'years' => now()->addYears($plan->new_user_discount_duration_value),
                            default => null,
                        };
                    }
                } elseif ($plan->new_user_discount_type === 'fixed') {
                    // Fixed amount discount
                    $discountAmount = min($plan->new_user_discount_value, $originalPrice);
                    $finalPrice = max(0, $originalPrice - $discountAmount);

                    // Calculate discount end date
                    if ($plan->new_user_discount_duration_value && $plan->new_user_discount_duration_unit) {
                        $discountEndsAt = match($plan->new_user_discount_duration_unit) {
                            'days' => now()->addDays($plan->new_user_discount_duration_value),
                            'months' => now()->addMonths($plan->new_user_discount_duration_value),
                            'years' => now()->addYears($plan->new_user_discount_duration_value),
                            default => null,
                        };
                    }
                }

                \Log::info('Desconto automático do plano aplicado', [
                    'user_id' => $user->id,
                    'plan' => $plan->name,
                    'discount_type' => $plan->new_user_discount_type,
                    'discount_amount' => $discountAmount,
                    'trial_days' => $trialDays,
                    'final_price' => $finalPrice,
                ]);
            }

            try {
                // Criar assinatura local
                $subscription = $user->subscriptions()->create([
                    'type' => $planName,
                    'stripe_id' => $isFreeCoupon ? 'coupon_' . uniqid() : 'subscription_' . uniqid(),
                    'stripe_status' => 'active',
                    'stripe_price' => $planInterval->stripe_price_id ?? 'price_' . $planInterval->id,
                    'quantity' => 1,
                    'trial_ends_at' => $trialDays ? now()->addDays($trialDays) : null,
                    'ends_at' => null,

                    // Coupon fields
                    'coupon_id' => $coupon?->id,
                    'original_price' => $originalPrice,
                    'discount_amount' => $discountAmount,
                    'final_price' => $finalPrice,
                    'discount_ends_at' => $discountEndsAt,
                ]);

                // Criar item da assinatura
                $subscription->items()->create([
                    'stripe_id' => 'item_' . uniqid(),
                    'stripe_product' => $planInterval->plan->stripe_product_id ?? 'product_' . $planInterval->plan_id,
                    'stripe_price' => $planInterval->stripe_price_id ?? 'price_' . $planInterval->id,
                    'quantity' => 1,
                ]);

                // Atualizar plano do team (se existir currentTeam)
                if ($user->currentTeam) {
                    $user->currentTeam->update([
                        'plan_id' => $planInterval->plan_id,
                    ]);
                } else {
                    \Log::warning('Usuário não tem currentTeam definido', [
                        'user_id' => $user->id,
                        'owned_teams' => $user->ownedTeams->pluck('id')->toArray(),
                    ]);

                    // Tentar usar o primeiro team do usuário
                    $firstTeam = $user->ownedTeams->first();
                    if ($firstTeam) {
                        $firstTeam->update([
                            'plan_id' => $planInterval->plan_id,
                        ]);

                        // Definir como current team
                        $user->forceFill(['current_team_id' => $firstTeam->id])->save();

                        \Log::info('Current team definido automaticamente', [
                            'user_id' => $user->id,
                            'team_id' => $firstTeam->id,
                        ]);
                    }
                }

                // Increment coupon usage if applied
                if ($coupon) {
                    $coupon->incrementUses();
                    session()->forget('applied_coupon');

                    \Log::info('Cupom aplicado com sucesso', [
                        'user_id' => $user->id,
                        'coupon_code' => $coupon->code,
                        'uses_count' => $coupon->uses_count,
                    ]);
                }

                \Log::info('Assinatura criada com sucesso', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'plan_name' => $planName,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'has_coupon' => $coupon !== null,
                ]);

                // Calculate duration in months for message
                $durationMonths = $trialDays ? ceil($trialDays / 30) : 1;

                $message = $isFreeCoupon
                    ? "Parabéns! Você ganhou acesso grátis por " . $durationMonths . " " . ($durationMonths == 1 ? 'mês' : 'meses') . " com seu cupom especial!"
                    : 'Assinatura criada com sucesso!';

                // Save welcome discount info in session for dashboard modal
                if ($applyPlanDiscount && $planInterval->plan->hasNewUserDiscount()) {
                    session([
                        'show_welcome_discount' => true,
                        'welcome_discount_type' => $planInterval->plan->new_user_discount_type,
                        'welcome_discount_text' => $planInterval->plan->getNewUserDiscountText(),
                        'welcome_plan_name' => $planInterval->plan->name,
                    ]);
                } elseif ($coupon && $isFreeCoupon) {
                    // Also show modal for coupon-based free access
                    session([
                        'show_welcome_discount' => true,
                        'welcome_discount_type' => 'trial',
                        'welcome_discount_text' => $durationMonths . " " . ($durationMonths == 1 ? 'mês grátis' : 'meses grátis'),
                        'welcome_plan_name' => $planInterval->plan->name,
                    ]);
                }

                return redirect()->route('dashboard')->with('success', $message);

            } catch (\Exception $e) {
                \Log::error('Erro ao criar assinatura', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->route('dashboard')->with('error', 'Erro ao criar assinatura. Tente novamente.');
            }
        }

        // Retorno padrão para requisições sem plano
        return redirect()->route('dashboard');
    }
}
