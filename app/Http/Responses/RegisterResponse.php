<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Models\Plan;
use App\Models\PlanInterval;
use App\Models\Coupon;
use Inertia\Inertia;
use App\Services\SubscriptionAccessRuleService;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

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
        $journey = $request->input('journey', 'subscription');

        if ($journey === 'trial') {
            return $this->handleTrialJourney($request, $user);
        }

        // Check if plan_interval is provided in the request
        if ($request->has('plan')) {
            $planIntervalId = $request->input('plan');

            $planInterval = PlanInterval::find($planIntervalId);

            if (!$planInterval) {
                return redirect()->route('dashboard')->with('error', 'Plano inválido');
            }

            $accessRules = app(SubscriptionAccessRuleService::class);
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
            if (
                !$coupon &&
                $accessRules->shouldAllowPlanNewUserDiscounts() &&
                $planInterval->plan->hasNewUserDiscount()
            ) {
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
            $trialDays = $accessRules->getDefaultTrialDays();
            $discountEndsAt = null;

            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($originalPrice);
                $finalPrice = max(0, $originalPrice - $discountAmount);

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

                    \Log::info('Cupom de 100% detectado - seguindo para checkout Stripe com benefício', [
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
                // Jornada de assinatura sempre formaliza via Stripe Checkout.
                // Mesmo com cupom 100% / período grátis, seguimos para Checkout
                // para coletar método de pagamento e iniciar a recorrência correta.
                $this->assignPlanToCurrentTeam($user, $planInterval, 'pendente');

                $checkoutUrl = $this->createStripeCheckoutSession(
                    $user,
                    $planInterval,
                    $coupon,
                    (float) $originalPrice,
                    (float) $discountAmount,
                    (float) $finalPrice,
                    0
                );

                if ($request->header('X-Inertia')) {
                    return Inertia::location($checkoutUrl);
                }

                return redirect()->away($checkoutUrl);
            } catch (\Exception $e) {
                \Log::error('Erro ao criar assinatura', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $message = app()->isLocal()
                    ? $e->getMessage()
                    : 'Erro ao criar assinatura. Tente novamente.';

                return redirect()->route('dashboard')->with('error', $message);
            }
        }

        // Retorno padrão para requisições sem plano
        return redirect()->route('dashboard');
    }

    private function assignPlanToCurrentTeam($user, PlanInterval $planInterval, string $status): void
    {
        $teamPayload = $this->buildTeamPlanPayload($planInterval, $status);

        if ($user->currentTeam) {
            $user->currentTeam->updateQuietly($teamPayload);
            return;
        }

        \Log::warning('Usuário não tem currentTeam definido', [
            'user_id' => $user->id,
            'owned_teams' => $user->ownedTeams->pluck('id')->toArray(),
        ]);

        $firstTeam = $user->ownedTeams->first();
        if (!$firstTeam) {
            return;
        }

        $firstTeam->updateQuietly($teamPayload);

        $user->forceFill(['current_team_id' => $firstTeam->id])->save();

        \Log::info('Current team definido automaticamente', [
            'user_id' => $user->id,
            'team_id' => $firstTeam->id,
        ]);
    }

    private function createStripeCheckoutSession(
        $user,
        PlanInterval $planInterval,
        ?Coupon $coupon,
        float $originalPrice,
        float $discountAmount,
        float $finalPrice,
        int $trialDays
    ): string {
        $this->warnIfProductionKeysLookInvalid();

        $secret = (string) config('cashier.secret');
        if ($secret === '') {
            throw new \RuntimeException('Stripe não está configurado no ambiente.');
        }

        $stripe = new StripeClient($secret);
        $user->createOrGetStripeCustomer();

        $stripePriceId = (string) ($planInterval->stripe_price_id ?? '');
        if ($stripePriceId === '') {
            throw new \RuntimeException('Este plano ainda não possui Stripe Price ID configurado.');
        }

        $discounts = $this->buildStripeDiscounts(
            stripe: $stripe,
            planInterval: $planInterval,
            coupon: $coupon,
            originalPrice: $originalPrice,
            discountAmount: $discountAmount,
            finalPrice: $finalPrice
        );

        $successUrl = route('subscriptions.success', ['mode' => 'stripe']);
        $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

        $payload = [
            'mode' => 'subscription',
            'customer' => $user->stripe_id,
            'client_reference_id' => (string) $user->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $stripePriceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => route('subscriptions.cancel'),
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_interval_id' => (string) $planInterval->id,
                'coupon_id' => (string) ($coupon?->id ?? 0),
                'original_price' => (string) $originalPrice,
                'discount_amount' => (string) $discountAmount,
                'final_price' => (string) $finalPrice,
                'trial_days' => (string) $trialDays,
            ],
            'locale' => 'pt-BR',
        ];

        if ($discounts !== []) {
            $payload['discounts'] = $discounts;
        }

        $session = $stripe->checkout->sessions->create($payload);

        return (string) $session->url;
    }

    private function warnIfProductionKeysLookInvalid(): void
    {
        if (!app()->isProduction()) {
            return;
        }

        $publishableKey = (string) config('cashier.key');
        $secretKey = (string) config('cashier.secret');

        if (!str_starts_with($publishableKey, 'pk_live_') || !str_starts_with($secretKey, 'sk_live_')) {
            \Log::warning('Stripe em produção com chaves não-LIVE detectadas no onboarding. Verifique STRIPE_KEY/STRIPE_SECRET.');
        }
    }

    private function buildTeamPlanPayload(PlanInterval $planInterval, string $status): array
    {
        $isActive = $status === 'ativo';
        $isFeaturedPlan = (bool) ($planInterval->plan?->is_featured ?? false);
        $shouldFeature = $isActive && $isFeaturedPlan;

        return [
            'plan_id' => $planInterval->plan_id,
            'status' => $status,
            'featured' => $shouldFeature,
            // Contratação ativa destaque sem prazo fixo.
            // O prazo passa a ser definido manualmente no admin quando necessário.
            'featured_until' => null,
        ];
    }

    /**
     * @return array<int, array{coupon: string}>
     */
    private function buildStripeDiscounts(
        StripeClient $stripe,
        PlanInterval $planInterval,
        ?Coupon $coupon,
        float $originalPrice,
        float $discountAmount,
        float $finalPrice
    ): array {
        if (!$coupon || $discountAmount <= 0 || $finalPrice >= $originalPrice) {
            return [];
        }

        $couponPayload = [
            'name' => 'TV-' . $coupon->code . '-' . now()->format('YmdHis'),
            'duration' => $this->resolveStripeCouponDuration($coupon),
            'metadata' => [
                'internal_coupon_id' => (string) $coupon->id,
                'internal_coupon_code' => (string) $coupon->code,
            ],
        ];

        if (($couponPayload['duration'] ?? null) === 'repeating') {
            $couponPayload['duration_in_months'] = $this->resolveStripeCouponDurationMonths($coupon);
        }

        if ($coupon->type === 'percentage') {
            $couponPayload['percent_off'] = (float) min(100, max(0, $coupon->value));
        } else {
            $currency = strtolower((string) ($planInterval->plan->currency ?? 'brl'));
            $amountOff = (int) max(1, round((float) $coupon->value * 100));
            $couponPayload['amount_off'] = $amountOff;
            $couponPayload['currency'] = $currency;
        }

        $stripeCoupon = $stripe->coupons->create($couponPayload);

        return [['coupon' => (string) $stripeCoupon->id]];
    }

    private function resolveStripeCouponDuration(Coupon $coupon): string
    {
        $value = (int) ($coupon->duration_value ?? 0);
        $unit = (string) ($coupon->duration_unit ?? '');

        if ($value <= 0) {
            return 'once';
        }

        return match ($unit) {
            'months', 'years', 'days' => 'repeating',
            default => 'once',
        };
    }

    private function resolveStripeCouponDurationMonths(Coupon $coupon): int
    {
        $value = max(1, (int) ($coupon->duration_value ?? 1));
        $unit = (string) ($coupon->duration_unit ?? 'months');

        $months = match ($unit) {
            'days' => max(1, (int) ceil($value / 30)),
            'months' => $value,
            'years' => $value * 12,
            default => 1,
        };

        return min($months, 36);
    }

    private function handleTrialJourney(Request $request, $user): RedirectResponse
    {
        $accessRules = app(SubscriptionAccessRuleService::class);
        $trialDays = $accessRules->getDefaultTrialDays();

        if ($trialDays <= 0) {
            return redirect()->route('dashboard')
                ->with('error', 'Período de teste está desativado no momento.');
        }

        $planInterval = $this->resolveTrialPlanInterval($request);

        if (!$planInterval) {
            return redirect()->route('dashboard')
                ->with('error', 'Nenhum plano disponível para teste no momento.');
        }

        $couponCode = strtoupper((string) ($request->input('coupon_code') ?: session('applied_coupon', '')));
        $couponCode = trim($couponCode);
        $appliedCoupon = null;

        if ($couponCode !== '') {
            $candidateCoupon = Coupon::where('code', $couponCode)->first();

            if ($candidateCoupon && $candidateCoupon->isValid()) {
                $couponDays = $this->resolveCouponDurationInDays($candidateCoupon);

                if ($couponDays > 0) {
                    // Em jornada trial, cupom define um período promocional maior.
                    $trialDays = max($trialDays, $couponDays);
                    $appliedCoupon = $candidateCoupon;
                } else {
                    \Log::info('Cupom informado no trial sem duração, ignorado para extensão de período.', [
                        'user_id' => $user->id,
                        'coupon_code' => $couponCode,
                    ]);
                }
            } else {
                \Log::warning('Cupom inválido informado na jornada trial.', [
                    'user_id' => $user->id,
                    'coupon_code' => $couponCode,
                ]);
            }
        }

        try {
            $originalPrice = (float) $planInterval->price;
            $discountAmount = $appliedCoupon ? $appliedCoupon->calculateDiscount($originalPrice) : 0.0;
            $finalPrice = max(0.0, $originalPrice - $discountAmount);
            $trialEndsAt = now()->addDays($trialDays);

            $subscription = $user->subscriptions()->create([
                'type' => $planInterval->plan->name . ' Trial',
                'stripe_id' => 'trial_' . uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => $planInterval->stripe_price_id ?? 'price_' . $planInterval->id,
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => null,
                'coupon_id' => $appliedCoupon?->id,
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'discount_ends_at' => $appliedCoupon ? $trialEndsAt : null,
            ]);

            $subscription->items()->create([
                'stripe_id' => 'item_' . uniqid(),
                'stripe_product' => $planInterval->plan->stripe_product_id ?? 'product_' . $planInterval->plan_id,
                'stripe_price' => $planInterval->stripe_price_id ?? 'price_' . $planInterval->id,
                'quantity' => 1,
            ]);

            $this->assignPlanToCurrentTeam($user, $planInterval, 'ativo');

            if ($appliedCoupon) {
                $appliedCoupon->incrementUses();
            }

            session([
                'show_welcome_discount' => true,
                'welcome_discount_type' => $appliedCoupon ? 'coupon_trial' : 'trial',
                'welcome_discount_text' => "{$trialDays} " . ($trialDays === 1 ? 'dia grátis' : 'dias grátis'),
                'welcome_plan_name' => $planInterval->plan->name,
                'welcome_coupon_code' => $appliedCoupon?->code,
            ]);

            // Garante que jornada de teste não herde cupom em sessão.
            session()->forget('applied_coupon');

            $successMessage = "Teste de {$trialDays} " . ($trialDays === 1 ? 'dia' : 'dias') . ' ativado com sucesso!';
            if ($appliedCoupon) {
                $successMessage .= " Cupom {$appliedCoupon->code} aplicado.";
            }

            return redirect()->route('dashboard')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar jornada de teste', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('dashboard')->with('error', 'Erro ao iniciar teste. Tente novamente.');
        }
    }

    private function resolveTrialPlanInterval(Request $request): ?PlanInterval
    {
        $planIntervalId = $request->input('plan');
        if ($planIntervalId) {
            $selected = PlanInterval::with(['plan', 'interval'])->find($planIntervalId);
            if ($selected && $selected->plan && $selected->plan->is_active) {
                return $selected;
            }
        }

        return PlanInterval::with(['plan', 'interval'])
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)
                    ->where('is_default', false);
            })
            ->join('plans', 'plan_intervals.plan_id', '=', 'plans.id')
            ->orderBy('plans.sort_order')
            ->orderBy('plan_intervals.price')
            ->select('plan_intervals.*')
            ->first();
    }

    private function resolveCouponDurationInDays(Coupon $coupon): int
    {
        $value = (int) ($coupon->duration_value ?? 0);
        $unit = (string) ($coupon->duration_unit ?? '');

        if ($value <= 0) {
            return 0;
        }

        return match ($unit) {
            'days' => $value,
            'months' => $value * 30,
            'years' => $value * 365,
            default => 0,
        };
    }
}
