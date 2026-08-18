<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Log;
use Exception;
use App\Models\Plan;
use App\Models\User;
use Inertia\Inertia;
use RuntimeException;
use App\Models\Coupon;
use Stripe\StripeClient;
use App\Models\PlanInterval;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SubscriptionAccessRuleService;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

final class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
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

            if (! $planInterval) {
                return redirect()->route('dashboard')->with('error', 'Plano inválido');
            }

            $accessRules = app(SubscriptionAccessRuleService::class);

            // Check if coupon was applied
            $coupon = null;
            $couponCode = session('applied_coupon');
            if ($couponCode) {
                $coupon = Coupon::where('code', mb_strtoupper($couponCode))->first();

                if ($coupon && $coupon->isValid()) {
                    Log::info('Cupom encontrado e válido', [
                        'user_id' => $user->id,
                        'coupon_code' => $coupon->code,
                        'discount' => $coupon->value,
                        'type' => $coupon->type,
                    ]);
                } else {
                    Log::warning('Cupom inválido ou expirado', [
                        'user_id' => $user->id,
                        'coupon_code' => $couponCode,
                    ]);
                    $coupon = null;
                }
            }

            // If no coupon, check if plan has automatic new user discount
            $applyPlanDiscount = false;
            if (
                ! $coupon &&
                $accessRules->shouldAllowPlanNewUserDiscounts() &&
                $planInterval->plan->hasNewUserDiscount()
            ) {
                Log::info('Aplicando desconto automático do plano', [
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
            $isFreePeriodCoupon = false;
            $trialDays = $accessRules->getDefaultTrialDays();

            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($originalPrice);
                $finalPrice = max(0, $originalPrice - $discountAmount);

                // Cupom que zera o plano e possui duração concede acesso local temporário.
                $isFreePeriodCoupon = $finalPrice <= 0 && $this->resolveCouponDurationInDays($coupon) > 0;

                if ($isFreePeriodCoupon) {
                    // Calculate trial days from coupon duration
                    $durationValue = $coupon->duration_value;
                    $durationUnit = $coupon->duration_unit;

                    // Convert to days
                    $trialDays = match ($durationUnit) {
                        'days' => $durationValue,
                        'months' => $durationValue * 30,
                        'years' => $durationValue * 365,
                        default => 0,
                    };

                    Log::info('Cupom de período grátis detectado - ativando trial local', [
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
                    Log::info('Cupom aplicado com desconto parcial', [
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
                } elseif ($plan->new_user_discount_type === 'percentage') {
                    // Percentage discount
                    $discountAmount = $originalPrice * ($plan->new_user_discount_value / 100);
                    $finalPrice = max(0, $originalPrice - $discountAmount);
                } elseif ($plan->new_user_discount_type === 'fixed') {
                    // Fixed amount discount
                    $discountAmount = min($plan->new_user_discount_value, $originalPrice);
                    $finalPrice = max(0, $originalPrice - $discountAmount);
                }

                Log::info('Desconto automático do plano aplicado', [
                    'user_id' => $user->id,
                    'plan' => $plan->name,
                    'discount_type' => $plan->new_user_discount_type,
                    'discount_amount' => $discountAmount,
                    'trial_days' => $trialDays,
                    'final_price' => $finalPrice,
                ]);
            }

            try {
                if ($coupon && $isFreePeriodCoupon) {
                    $subscription = $this->activateFreeCouponTrial(
                        user: $user,
                        planInterval: $planInterval,
                        coupon: $coupon,
                        originalPrice: (float) $originalPrice,
                        discountAmount: (float) $discountAmount,
                        finalPrice: (float) $finalPrice,
                        trialDays: $trialDays
                    );

                    $durationText = $coupon->getDurationText() ?? "{$trialDays} dias";

                    session([
                        'show_welcome_discount' => true,
                        'welcome_discount_type' => 'coupon_trial',
                        'welcome_discount_text' => "{$durationText} grátis",
                        'welcome_plan_name' => $planInterval->plan->name,
                        'welcome_coupon_code' => $coupon->code,
                    ]);
                    session()->forget('applied_coupon');

                    Log::info('Trial promocional ativado sem Stripe', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'coupon_code' => $coupon->code,
                        'trial_ends_at' => $subscription->trial_ends_at,
                    ]);

                    return redirect()->route('dashboard')->with(
                        'success',
                        "Parabéns! Você ganhou {$durationText} grátis no plano {$planInterval->plan->name}."
                    );
                }

                // Assinatura sem benefício integral e desconto parcial seguem para a Stripe.
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
            } catch (Exception $e) {
                Log::error('Erro ao criar assinatura', [
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

    private function activateFreeCouponTrial(
        User $user,
        PlanInterval $planInterval,
        Coupon $coupon,
        float $originalPrice,
        float $discountAmount,
        float $finalPrice,
        int $trialDays
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $planInterval,
            $coupon,
            $originalPrice,
            $discountAmount,
            $finalPrice,
            $trialDays
        ): Subscription {
            /** @var Coupon|null $lockedCoupon */
            $lockedCoupon = Coupon::query()->lockForUpdate()->find($coupon->id);

            if (! $lockedCoupon || ! $lockedCoupon->isValid()) {
                throw new RuntimeException('Cupom inválido ou expirado.');
            }

            $trialEndsAt = $lockedCoupon->calculateExpirationDate() ?? now()->addDays($trialDays);
            $stripePrice = $planInterval->stripe_price_id ?: 'price_'.$planInterval->id;
            $subscriptionType = $planInterval->plan->name.' Trial Promocional';

            /** @var Subscription $subscription */
            $subscription = $user->subscriptions()->updateOrCreate(
                ['name' => 'default'],
                [
                    'type' => $subscriptionType,
                    'stripe_id' => 'coupon_'.uniqid(),
                    'stripe_status' => 'active',
                    'stripe_price' => $stripePrice,
                    'quantity' => 1,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => null,
                    'coupon_id' => $lockedCoupon->id,
                    'original_price' => $originalPrice,
                    'discount_amount' => $discountAmount,
                    'final_price' => $finalPrice,
                    'discount_ends_at' => $trialEndsAt->copy(),
                ]
            );

            $subscriptionItem = $subscription->items()->first();
            $itemPayload = [
                'stripe_product' => $planInterval->plan->stripe_product_id ?? 'product_'.$planInterval->plan_id,
                'stripe_price' => $stripePrice,
                'quantity' => 1,
            ];

            if ($subscriptionItem) {
                $subscriptionItem->update($itemPayload);
            } else {
                $subscription->items()->create([
                    'stripe_id' => 'item_'.uniqid(),
                    ...$itemPayload,
                ]);
            }

            $this->assignPlanToCurrentTeam($user, $planInterval, 'ativo');
            $lockedCoupon->incrementUses();

            return $subscription->refresh();
        });
    }

    private function assignPlanToCurrentTeam($user, PlanInterval $planInterval, string $status): void
    {
        $teamPayload = $this->buildTeamPlanPayload($planInterval, $status);

        if ($user->currentTeam) {
            $user->currentTeam->updateQuietly($teamPayload);

            return;
        }

        Log::warning('Usuário não tem currentTeam definido', [
            'user_id' => $user->id,
            'owned_teams' => $user->ownedTeams->pluck('id')->toArray(),
        ]);

        $firstTeam = $user->ownedTeams->first();
        if (! $firstTeam) {
            return;
        }

        $firstTeam->updateQuietly($teamPayload);

        $user->forceFill(['current_team_id' => $firstTeam->id])->save();

        Log::info('Current team definido automaticamente', [
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
            throw new RuntimeException('Stripe não está configurado no ambiente.');
        }

        $stripe = new StripeClient($secret);
        $user->createOrGetStripeCustomer();

        $stripePriceId = (string) ($planInterval->stripe_price_id ?? '');
        if ($stripePriceId === '') {
            throw new RuntimeException('Este plano ainda não possui Stripe Price ID configurado.');
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
        $successUrl .= (str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}';

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
        if (! app()->isProduction()) {
            return;
        }

        $publishableKey = (string) config('cashier.key');
        $secretKey = (string) config('cashier.secret');

        if (! str_starts_with($publishableKey, 'pk_live_') || ! str_starts_with($secretKey, 'sk_live_')) {
            Log::warning('Stripe em produção com chaves não-LIVE detectadas no onboarding. Verifique STRIPE_KEY/STRIPE_SECRET.');
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
        if (! $coupon || $discountAmount <= 0 || $finalPrice >= $originalPrice) {
            return [];
        }

        $couponPayload = [
            'name' => 'TV-'.$coupon->code.'-'.now()->format('YmdHis'),
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
            $currency = mb_strtolower((string) ($planInterval->plan->currency ?? 'brl'));
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

        if (! $planInterval) {
            return redirect()->route('dashboard')
                ->with('error', 'Nenhum plano disponível para teste no momento.');
        }

        $couponCode = mb_strtoupper((string) ($request->input('coupon_code') ?: session('applied_coupon', '')));
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
                    Log::info('Cupom informado no trial sem duração, ignorado para extensão de período.', [
                        'user_id' => $user->id,
                        'coupon_code' => $couponCode,
                    ]);
                }
            } else {
                Log::warning('Cupom inválido informado na jornada trial.', [
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
                'type' => $planInterval->plan->name.' Trial',
                'stripe_id' => 'trial_'.uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => $planInterval->stripe_price_id ?? 'price_'.$planInterval->id,
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
                'stripe_id' => 'item_'.uniqid(),
                'stripe_product' => $planInterval->plan->stripe_product_id ?? 'product_'.$planInterval->plan_id,
                'stripe_price' => $planInterval->stripe_price_id ?? 'price_'.$planInterval->id,
                'quantity' => 1,
            ]);

            $this->assignPlanToCurrentTeam($user, $planInterval, 'ativo');

            if ($appliedCoupon) {
                $appliedCoupon->incrementUses();
            }

            session([
                'show_welcome_discount' => true,
                'welcome_discount_type' => $appliedCoupon ? 'coupon_trial' : 'trial',
                'welcome_discount_text' => "{$trialDays} ".($trialDays === 1 ? 'dia grátis' : 'dias grátis'),
                'welcome_plan_name' => $planInterval->plan->name,
                'welcome_coupon_code' => $appliedCoupon?->code,
            ]);

            // Garante que jornada de teste não herde cupom em sessão.
            session()->forget('applied_coupon');

            $successMessage = "Teste de {$trialDays} ".($trialDays === 1 ? 'dia' : 'dias').' ativado com sucesso!';
            if ($appliedCoupon) {
                $successMessage .= " Cupom {$appliedCoupon->code} aplicado.";
            }

            return redirect()->route('dashboard')
                ->with('success', $successMessage);
        } catch (Exception $e) {
            Log::error('Erro ao criar jornada de teste', [
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
