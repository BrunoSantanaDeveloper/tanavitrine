<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\PlanInterval;
use App\Models\User;
use App\Models\Subscription;
use App\Services\SubscriptionAccessRuleService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\StripeClient;
use Stripe\Webhook as StripeWebhook;

final class SubscriptionController extends Controller
{
    /**
     * Redirect authenticated user to subscriptions management page.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('subscriptions.create');
    }

    /**
     * Display subscription management page with active subscriptions and available plans.
     */
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $accessRules = app(SubscriptionAccessRuleService::class);

        /** @var Collection<int, Subscription> $activeSubscriptions */
        $activeSubscriptions = Subscription::query()
            ->where(['user_id' => $user->id])
            ->active()
            ->get();

        // Get team and current plan
        $team = $user->currentTeam;
        $currentPlan = null;
        $limits = [];
        $usage = [];

        // Buscar assinatura atual do usuário (prioriza a assinatura default mais recente)
        $activeSubscription = $user->subscriptions()
            ->where('name', 'default')
            ->latest()
            ->first();

        if ($activeSubscription) {
            $subscriptionItem = $activeSubscription->items->first();

            // Tentar encontrar plano pelo stripe_product ou stripe_price
            if ($subscriptionItem) {
                $currentPlan = Plan::with(['intervals', 'limits'])
                    ->where('stripe_product_id', $subscriptionItem->stripe_product)
                    ->orWhere(function ($query) use ($subscriptionItem) {
                        $query->whereHas('intervals', function ($q) use ($subscriptionItem) {
                            $q->where('stripe_price_id', $subscriptionItem->stripe_price);
                        });
                    })
                    ->first();
            }

            // Fallback: usar plano do team se não encontrou pelo Stripe
            if (!$currentPlan && $team && $team->plan_id) {
                $currentPlan = Plan::with(['intervals', 'limits'])->find($team->plan_id);
            }

            if ($currentPlan) {
                // Add price and interval info to currentPlan
                if ($currentPlan->intervals->isNotEmpty()) {
                    // Try to find the interval based on subscription's stripe_price
                    $planInterval = $currentPlan->intervals->first(function ($interval) use ($activeSubscription) {
                        return $interval->pivot->stripe_price_id === $activeSubscription->stripe_price;
                    });

                    // If found, add price and interval to currentPlan
                    if ($planInterval) {
                        $currentPlan->current_price = floatval($planInterval->pivot->price);
                        $currentPlan->current_interval = $planInterval->name;
                    } else {
                        // If no matching interval found, use the first one as fallback
                        $firstInterval = $currentPlan->intervals->first();
                        $currentPlan->current_price = floatval($firstInterval->pivot->price);
                        $currentPlan->current_interval = $firstInterval->name;
                    }
                }

                $currentPlan->subscription = $accessRules->buildSubscriptionMeta($activeSubscription);

                $limits = $currentPlan->limits->pluck('limit_value', 'resource')->toArray();

                // Calculate current usage
                $usage = [
                    // Add usage calculation here if needed
                ];
            }
        } elseif ($team && $team->plan_id) {
            // Fallback: usar plano do team se não tiver subscription
            $currentPlan = Plan::with(['intervals', 'limits'])->find($team->plan_id);

            if ($currentPlan && $currentPlan->intervals->isNotEmpty()) {
                $firstInterval = $currentPlan->intervals->first();
                $currentPlan->current_price = floatval($firstInterval->pivot->price);
                $currentPlan->current_interval = $firstInterval->name;
                $limits = $currentPlan->limits->pluck('limit_value', 'resource')->toArray();
                $usage = [];
            }
        }

        // Get all plans with intervals and limits
        $plans = Plan::with([
            'intervals' => function ($query) {
                $query->withPivot(['id', 'price', 'stripe_price_id']);
            },
            'limits'
        ])->get();

        return Inertia::render('Subscriptions/Index', [
            'activeSubscriptions' => $activeSubscriptions,
            'availableSubscriptions' => config('subscriptions.subscriptions'),
            'activeInvoices' => Inertia::defer(fn () => $user->invoices()),
            'plans' => $plans,
            'currentPlan' => $currentPlan,
            'limits' => $limits,
            'usage' => $usage,
        ]);
    }


    /**
     * Create subscription/upgrade checkout for selected plan interval.
     * - Sempre segue para Stripe Checkout (inclusive cupom 100%/período grátis),
     *   para coletar cartão e iniciar cobrança ao fim do benefício.
     */
    public function checkout(Request $request, string $plan_interval_id): JsonResponse|RedirectResponse
    {
        Log::info('Checkout attempt', [
            'plan_interval_id' => $plan_interval_id,
            'user_id' => auth()->id(),
        ]);

        try {
            /** @var User|null $user */
            $user = auth()->user();

            if (!$user) {
                $message = 'Por favor, faça login para continuar.';
                return $request->expectsJson()
                    ? response()->json(['message' => $message], 401)
                    : redirect()->route('login')->with('error', $message);
            }

            $planInterval = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->whereKey($plan_interval_id)
                ->first();

            if (!$planInterval || !$planInterval->plan || !$planInterval->interval) {
                throw new RuntimeException('Plano não encontrado.');
            }

            $coupon = $this->resolveCouponForCheckout($request);
            $pricing = $this->buildCheckoutPricing($planInterval, $coupon);
            $carryoverTrialDays = $this->resolveActiveDiscountTrialCarryoverDays($user, $coupon);

            if ($carryoverTrialDays > 0) {
                $pricing['trial_days'] = max($pricing['trial_days'], $carryoverTrialDays);

                Log::info('Applying active discount carryover trial for Stripe checkout', [
                    'user_id' => $user->id,
                    'plan_interval_id' => $planInterval->id,
                    'trial_days' => $pricing['trial_days'],
                ]);
            }

            $session = $this->createStripeCheckoutSession($user, $planInterval, $coupon, $pricing);

            return $request->expectsJson()
                ? response()->json(['checkout_url' => (string) $session->url])
                : redirect()->away((string) $session->url);
        } catch (\Throwable $e) {
            Log::error('Error creating checkout session', [
                'error' => $e->getMessage(),
                'plan_interval_id' => $plan_interval_id,
                'user_id' => auth()->id(),
            ]);

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível iniciar a assinatura agora. Tente novamente.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->route('dashboard')->with('error', $message);
        }
    }

    /**
     * Change plan for an already formalized active subscription.
     * This flow updates Stripe subscription directly (no new Checkout).
     */
    public function changePlan(Request $request, string $plan_interval_id): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Por favor, faça login para continuar.'], 401);
            }

            /** @var Subscription|null $localSubscription */
            $localSubscription = $user->subscriptions()
                ->where('name', 'default')
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->latest()
                ->first();

            if (!$this->isFormalizedLocalSubscription($localSubscription)) {
                return response()->json([
                    'message' => 'Sua assinatura ainda não está formalizada. Use o fluxo de assinatura.',
                ], 422);
            }

            $targetPlanInterval = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->whereKey($plan_interval_id)
                ->first();

            if (!$targetPlanInterval || !$targetPlanInterval->plan || !$targetPlanInterval->interval) {
                throw new RuntimeException('Plano não encontrado.');
            }

            $targetStripePriceId = (string) ($targetPlanInterval->stripe_price_id ?? '');
            if ($targetStripePriceId === '') {
                throw new RuntimeException('Plano sem Stripe Price ID configurado.');
            }

            $currentPlan = $user->currentTeam?->plan;
            if (!$currentPlan) {
                throw new RuntimeException('Plano atual não identificado.');
            }

            if ((int) $currentPlan->id === (int) $targetPlanInterval->plan_id) {
                return response()->json(['message' => 'Este já é o plano atual da sua assinatura.'], 422);
            }

            $isDowngrade = $this->comparePlansForMigration($targetPlanInterval->plan, $currentPlan) < 0;

            $stripe = $this->makeStripeClient();
            $stripeSubscription = $this->findStripeManagedSubscription($user, $stripe);
            if (!$stripeSubscription) {
                throw new RuntimeException('Não foi possível localizar a assinatura ativa no Stripe.');
            }

            $currentStripeItem = $stripeSubscription->items->data[0] ?? null;
            if (!$currentStripeItem) {
                throw new RuntimeException('Não foi possível localizar o item da assinatura no Stripe.');
            }

            $updatedStripeSubscription = $stripe->subscriptions->update((string) $stripeSubscription->id, [
                'items' => [[
                    'id' => (string) $currentStripeItem->id,
                    'price' => $targetStripePriceId,
                ]],
                // Evita cobrança imediata inesperada e não exige novo checkout.
                'proration_behavior' => 'none',
            ]);

            $nextBillingDate = !empty($updatedStripeSubscription->current_period_end)
                ? now()->setTimestamp((int) $updatedStripeSubscription->current_period_end)
                : null;

            $trialEndsAtFromStripe = !empty($updatedStripeSubscription->trial_end)
                ? now()->setTimestamp((int) $updatedStripeSubscription->trial_end)
                : null;

            $basePrice = (float) $targetPlanInterval->price;
            $coupon = $localSubscription?->coupon_id ? Coupon::find($localSubscription->coupon_id) : null;
            $discountEndsAt = $localSubscription?->discount_ends_at;
            $hasDiscountWindow = $discountEndsAt && $discountEndsAt->isFuture();

            $discountAmount = 0.0;
            $finalPrice = $basePrice;

            if ($coupon && $hasDiscountWindow) {
                $discountAmount = min($coupon->calculateDiscount($basePrice), $basePrice);
                $finalPrice = max(0.0, $basePrice - $discountAmount);
            } elseif (!$trialEndsAtFromStripe) {
                $coupon = null;
                $discountEndsAt = null;
            }

            $localSubscription->update([
                'type' => $targetPlanInterval->plan->name . ' ' . $targetPlanInterval->interval->name,
                'stripe_status' => (string) $updatedStripeSubscription->status,
                'stripe_price' => $targetStripePriceId,
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAtFromStripe,
                'ends_at' => null,
                'coupon_id' => $coupon?->id,
                'original_price' => $basePrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'discount_ends_at' => $discountEndsAt,
            ]);

            $localSubscriptionItem = $localSubscription->items()->first();
            if ($localSubscriptionItem) {
                $localSubscriptionItem->update([
                    'stripe_product' => $targetPlanInterval->plan->stripe_product_id ?? $localSubscriptionItem->stripe_product,
                    'stripe_price' => $targetStripePriceId,
                    'quantity' => 1,
                ]);
            }

            $team = $user->currentTeam ?: $user->ownedTeams()->first();
            if ($team) {
                $team->updateQuietly($this->buildTeamPlanPayload($targetPlanInterval));
            }

            $message = $isDowngrade
                ? 'Downgrade aplicado com sucesso. Sua assinatura segue ativa sem novo checkout.'
                : 'Upgrade aplicado com sucesso. Sua assinatura segue ativa sem novo checkout.';

            return response()->json([
                'message' => $message,
                'change_type' => $isDowngrade ? 'downgrade' : 'upgrade',
                'from_plan' => $currentPlan->name,
                'to_plan' => $targetPlanInterval->plan->name,
                'next_billing_date' => $nextBillingDate?->format('d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error changing active plan', [
                'error' => $e->getMessage(),
                'plan_interval_id' => $plan_interval_id,
                'user_id' => auth()->id(),
            ]);

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível alterar o plano agora. Tente novamente.';

            return response()->json(['message' => $message], 422);
        }
    }

    /**
     * Schedule cancellation at period end for a formalized Stripe subscription.
     */
    public function cancelPlan(Request $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Por favor, faça login para continuar.'], 401);
            }

            /** @var Subscription|null $localSubscription */
            $localSubscription = $user->subscriptions()
                ->where('name', 'default')
                ->whereIn('stripe_status', ['active', 'trialing', 'past_due', 'unpaid'])
                ->latest()
                ->first();

            if (!$this->isFormalizedLocalSubscription($localSubscription)) {
                return response()->json([
                    'message' => 'Sua assinatura ainda não está formalizada.',
                ], 422);
            }

            $stripe = $this->makeStripeClient();
            $stripeSubscription = $this->findStripeManagedSubscription($user, $stripe);
            if (!$stripeSubscription) {
                throw new RuntimeException('Não foi possível localizar a assinatura ativa no Stripe.');
            }

            if ((bool) ($stripeSubscription->cancel_at_period_end ?? false)) {
                $periodEnd = !empty($stripeSubscription->current_period_end)
                    ? now()->setTimestamp((int) $stripeSubscription->current_period_end)
                    : $localSubscription->ends_at;

                if ($periodEnd) {
                    $localSubscription->updateQuietly([
                        'stripe_status' => (string) $stripeSubscription->status,
                        'ends_at' => $periodEnd,
                    ]);
                }

                return response()->json([
                    'message' => 'Cancelamento já agendado.',
                    'ends_at' => $periodEnd?->format('d/m/Y'),
                ]);
            }

            $updatedStripeSubscription = $stripe->subscriptions->update((string) $stripeSubscription->id, [
                'cancel_at_period_end' => true,
            ]);

            $periodEnd = !empty($updatedStripeSubscription->current_period_end)
                ? now()->setTimestamp((int) $updatedStripeSubscription->current_period_end)
                : null;

            $localSubscription->update([
                'stripe_status' => (string) $updatedStripeSubscription->status,
                'ends_at' => $periodEnd,
            ]);

            return response()->json([
                'message' => 'Cancelamento agendado para o fim do ciclo.',
                'ends_at' => $periodEnd?->format('d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error scheduling subscription cancellation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível agendar o cancelamento agora.';

            return response()->json(['message' => $message], 422);
        }
    }

    /**
     * Resume a cancellation previously scheduled for period end.
     */
    public function resumePlan(Request $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Por favor, faça login para continuar.'], 401);
            }

            /** @var Subscription|null $localSubscription */
            $localSubscription = $user->subscriptions()
                ->where('name', 'default')
                ->whereIn('stripe_status', ['active', 'trialing', 'past_due', 'unpaid'])
                ->latest()
                ->first();

            if (!$this->isFormalizedLocalSubscription($localSubscription)) {
                return response()->json([
                    'message' => 'Sua assinatura ainda não está formalizada.',
                ], 422);
            }

            $stripe = $this->makeStripeClient();
            $stripeSubscription = $this->findStripeManagedSubscription($user, $stripe);
            if (!$stripeSubscription) {
                throw new RuntimeException('Não foi possível localizar a assinatura ativa no Stripe.');
            }

            if (!(bool) ($stripeSubscription->cancel_at_period_end ?? false)) {
                return response()->json([
                    'message' => 'Sua assinatura já está ativa sem cancelamento agendado.',
                ]);
            }

            $updatedStripeSubscription = $stripe->subscriptions->update((string) $stripeSubscription->id, [
                'cancel_at_period_end' => false,
            ]);

            $localSubscription->update([
                'stripe_status' => (string) $updatedStripeSubscription->status,
                'ends_at' => null,
            ]);

            return response()->json([
                'message' => 'Cancelamento removido. Sua assinatura segue ativa.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error resuming subscription cancellation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível reativar a assinatura agora.';

            return response()->json(['message' => $message], 422);
        }
    }

    public function success(Request $request): Response|RedirectResponse
    {
        $sessionId = (string) $request->get('session_id', '');
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user || $sessionId === '') {
            return redirect()->route('dashboard')->with('error', 'Sessão inválida.');
        }

        Log::info('Success request', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'user_verified' => $user->hasVerifiedEmail(),
        ]);

        if (str_starts_with($sessionId, 'local_')) {
            $subscriptionId = (int) str_replace('local_', '', $sessionId);
            $subscription = $user->subscriptions()->find($subscriptionId);

            if (!$subscription) {
                return redirect()->route('dashboard')->with('error', 'Assinatura não encontrada.');
            }

            return $this->renderSuccessResponse($user, $subscription, true);
        }

        if (!str_starts_with($sessionId, 'cs_')) {
            return redirect()->route('dashboard')->with('error', 'Sessão de checkout inválida.');
        }

        // Idempotência: se já processamos esta sessão Stripe, apenas renderiza sucesso.
        $existingSubscription = $user->subscriptions()
            ->where('stripe_id', $sessionId)
            ->latest()
            ->first();

        if ($existingSubscription) {
            return $this->renderSuccessResponse($user, $existingSubscription, false);
        }

        try {
            $stripe = $this->makeStripeClient();
            $session = $stripe->checkout->sessions->retrieve($sessionId, []);

            $isPaid = in_array($session->payment_status, ['paid', 'no_payment_required'], true);
            if (!$isPaid) {
                return redirect()->route('dashboard')->with('error', 'Pagamento ainda não confirmado.');
            }

            $metadata = $session->metadata;
            $metadata = is_array($metadata)
                ? $metadata
                : ($metadata ? $metadata->toArray() : []);
            $planIntervalId = (int) ($metadata['plan_interval_id'] ?? 0);
            $couponId = (int) ($metadata['coupon_id'] ?? 0);

            $planInterval = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->whereKey($planIntervalId)
                ->first();

            if (!$planInterval || !$planInterval->plan || !$planInterval->interval) {
                throw new RuntimeException('Plano do checkout não encontrado.');
            }

            $coupon = $couponId > 0 ? Coupon::find($couponId) : null;
            $pricing = $this->buildPricingFromMetadata($metadata, $planInterval, $coupon);

            $subscription = $this->activateSubscriptionLocally(
                user: $user,
                planInterval: $planInterval,
                coupon: $coupon,
                pricing: $pricing,
                stripeReference: $sessionId,
                incrementCouponUses: $coupon !== null
            );

            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return $this->renderSuccessResponse($user, $subscription, false);
        } catch (\Throwable $e) {
            Log::error('Error processing checkout success', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')->with('error', 'Não foi possível confirmar este pagamento.');
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = (string) config('cashier.webhook.secret');

        if ($this->isProductionStripeEnvironment() && $webhookSecret === '') {
            Log::warning('Stripe webhook sem assinatura ativa: STRIPE_WEBHOOK_SECRET ausente em produção.');
        }

        try {
            $event = $webhookSecret !== ''
                ? StripeWebhook::constructEvent($payload, $signature, $webhookSecret)
                : json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Invalid Stripe webhook payload/signature', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['received' => false], 400);
        }

        $eventType = (string) ($event->type ?? '');
        if ($eventType !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = $event->data->object ?? null;
        if (!$session) {
            return response()->json(['received' => true]);
        }

        try {
            $sessionId = (string) ($session->id ?? '');
            if ($sessionId === '') {
                return response()->json(['received' => true]);
            }

            $metadata = $session->metadata ?? [];
            $metadata = is_array($metadata)
                ? $metadata
                : ($metadata ? $metadata->toArray() : []);

            $userId = (int) ($metadata['user_id'] ?? 0);
            $planIntervalId = (int) ($metadata['plan_interval_id'] ?? 0);
            $couponId = (int) ($metadata['coupon_id'] ?? 0);

            if ($userId <= 0 || $planIntervalId <= 0) {
                return response()->json(['received' => true]);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json(['received' => true]);
            }

            // Idempotência do webhook.
            $alreadyProcessed = $user->subscriptions()
                ->where('stripe_id', $sessionId)
                ->exists();

            if ($alreadyProcessed) {
                return response()->json(['received' => true]);
            }

            $planInterval = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->whereKey($planIntervalId)
                ->first();

            if (!$planInterval || !$planInterval->plan || !$planInterval->interval) {
                return response()->json(['received' => true]);
            }

            $coupon = $couponId > 0 ? Coupon::find($couponId) : null;
            $pricing = $this->buildPricingFromMetadata($metadata, $planInterval, $coupon);

            $this->activateSubscriptionLocally(
                user: $user,
                planInterval: $planInterval,
                coupon: $coupon,
                pricing: $pricing,
                stripeReference: $sessionId,
                incrementCouponUses: $coupon !== null
            );
        } catch (\Throwable $e) {
            Log::error('Error handling Stripe webhook', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
            ]);
        }

        return response()->json(['received' => true]);
    }

    public function cancel(): RedirectResponse
    {
        session()->forget('checkout');

        return redirect()
            ->route('subscriptions.create')
            ->with('warning', 'Pagamento cancelado. Você pode tentar novamente quando quiser.');
    }

    private function resolveCouponForCheckout(Request $request): ?Coupon
    {
        $couponCode = strtoupper((string) $request->input('coupon_code', ''));
        $couponCode = trim($couponCode);

        if ($couponCode !== '') {
            $coupon = Coupon::query()->where('code', $couponCode)->first();

            if (!$coupon || !$coupon->isValid()) {
                throw new RuntimeException('Cupom inválido ou expirado.');
            }

            return $coupon;
        }

        return null;
    }

    private function resolveActiveDiscountTrialCarryoverDays(User $user, ?Coupon $coupon): int
    {
        // Se o usuário informou cupom explicitamente, seguimos a regra do cupom informado.
        if ($coupon !== null) {
            return 0;
        }

        $activeSubscription = $user->subscriptions()
            ->where('name', 'default')
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->latest()
            ->first();

        if (!$activeSubscription || !$activeSubscription->hasActiveDiscount()) {
            return 0;
        }

        return max(0, (int) ($activeSubscription->getDiscountDaysRemaining() ?? 0));
    }

    /**
     * @return array{
     *   original_price: float,
     *   discount_amount: float,
     *   final_price: float,
     *   trial_days: int
     * }
     */
    private function buildCheckoutPricing(PlanInterval $planInterval, ?Coupon $coupon): array
    {
        $originalPrice = (float) $planInterval->price;
        $discountAmount = 0.0;
        $finalPrice = $originalPrice;
        $trialDays = 0;

        if ($coupon) {
            $discountAmount = min($coupon->calculateDiscount($originalPrice), $originalPrice);
            $finalPrice = max(0.0, $originalPrice - $discountAmount);

            if ($finalPrice <= 0) {
                $trialDays = $this->couponDurationInDays($coupon);
            }
        }

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'trial_days' => $trialDays,
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{
     *   original_price: float,
     *   discount_amount: float,
     *   final_price: float,
     *   trial_days: int
     * }
     */
    private function buildPricingFromMetadata(array $metadata, PlanInterval $planInterval, ?Coupon $coupon): array
    {
        if (isset($metadata['original_price'], $metadata['discount_amount'], $metadata['final_price'])) {
            return [
                'original_price' => (float) $metadata['original_price'],
                'discount_amount' => (float) $metadata['discount_amount'],
                'final_price' => (float) $metadata['final_price'],
                'trial_days' => (int) ($metadata['trial_days'] ?? 0),
            ];
        }

        return $this->buildCheckoutPricing($planInterval, $coupon);
    }

    private function couponDurationInDays(Coupon $coupon): int
    {
        $value = (int) ($coupon->duration_value ?? 0);
        $unit = (string) ($coupon->duration_unit ?? '');

        if ($value <= 0) {
            return app(SubscriptionAccessRuleService::class)->getDefaultTrialDays();
        }

        return match ($unit) {
            'days' => $value,
            'months' => $value * 30,
            'years' => $value * 365,
            default => app(SubscriptionAccessRuleService::class)->getDefaultTrialDays(),
        };
    }

    /**
     * @param array{
     *   original_price: float,
     *   discount_amount: float,
     *   final_price: float,
     *   trial_days: int
     * } $pricing
     */
    private function createStripeCheckoutSession(
        User $user,
        PlanInterval $planInterval,
        ?Coupon $coupon,
        array $pricing
    ): StripeCheckoutSession {
        $stripe = $this->makeStripeClient();

        $user->createOrGetStripeCustomer();

        $stripePriceId = (string) ($planInterval->stripe_price_id ?? '');
        if ($stripePriceId === '') {
            throw new RuntimeException('Este plano ainda não possui Stripe Price ID configurado.');
        }

        $discounts = $this->buildStripeDiscounts(
            stripe: $stripe,
            planInterval: $planInterval,
            coupon: $coupon,
            pricing: $pricing
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
                'original_price' => (string) $pricing['original_price'],
                'discount_amount' => (string) $pricing['discount_amount'],
                'final_price' => (string) $pricing['final_price'],
                'trial_days' => (string) $pricing['trial_days'],
            ],
            'locale' => 'pt-BR',
        ];

        if ($discounts !== []) {
            $payload['discounts'] = $discounts;
        }

        if (($pricing['trial_days'] ?? 0) > 0) {
            $payload['subscription_data'] = [
                'trial_period_days' => (int) $pricing['trial_days'],
            ];
        }

        return $stripe->checkout->sessions->create($payload);
    }

    private function makeStripeClient(): StripeClient
    {
        $this->warnIfProductionKeysLookInvalid();

        $secret = (string) config('cashier.secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe não está configurado no ambiente.');
        }

        return new StripeClient($secret);
    }

    private function warnIfProductionKeysLookInvalid(): void
    {
        if (!$this->isProductionStripeEnvironment()) {
            return;
        }

        $publishableKey = (string) config('cashier.key');
        $secretKey = (string) config('cashier.secret');

        if (!str_starts_with($publishableKey, 'pk_live_') || !str_starts_with($secretKey, 'sk_live_')) {
            Log::warning('Stripe em produção com chaves não-LIVE detectadas. Verifique STRIPE_KEY/STRIPE_SECRET.');
        }
    }

    private function isProductionStripeEnvironment(): bool
    {
        return app()->isProduction();
    }

    /**
     * @param array{
     *   original_price: float,
     *   discount_amount: float,
     *   final_price: float,
     *   trial_days: int
     * } $pricing
     * @return array<int, array{coupon: string}>
     */
    private function buildStripeDiscounts(
        StripeClient $stripe,
        PlanInterval $planInterval,
        ?Coupon $coupon,
        array $pricing
    ): array {
        if (!$coupon || $pricing['discount_amount'] <= 0 || $pricing['final_price'] <= 0) {
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

    /**
     * @param array{
     *   original_price: float,
     *   discount_amount: float,
     *   final_price: float,
     *   trial_days: int
     * } $pricing
     */
    private function activateSubscriptionLocally(
        User $user,
        PlanInterval $planInterval,
        ?Coupon $coupon,
        array $pricing,
        string $stripeReference,
        bool $incrementCouponUses
    ): Subscription {
        $team = $user->currentTeam ?: $user->ownedTeams()->first();

        if ($team) {
            $team->updateQuietly($this->buildTeamPlanPayload($planInterval));

            if (!$user->current_team_id) {
                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        }

        $trialEndsAt = $pricing['trial_days'] > 0 ? now()->addDays($pricing['trial_days']) : null;
        $discountEndsAt = $coupon?->calculateExpirationDate();

        if (!$discountEndsAt && $trialEndsAt) {
            $discountEndsAt = $trialEndsAt->copy();
        }

        $stripePrice = $planInterval->stripe_price_id ?: 'price_' . $planInterval->id;
        $subscriptionType = $planInterval->plan->name . ' ' . $planInterval->interval->name;

        $subscription = $user->subscriptions()
            ->where('name', 'default')
            ->latest()
            ->first();

        $payload = [
            'name' => 'default',
            'type' => $subscriptionType,
            'stripe_id' => $stripeReference,
            'stripe_status' => 'active',
            'stripe_price' => $stripePrice,
            'quantity' => 1,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => null,
            'coupon_id' => $coupon?->id,
            'original_price' => $pricing['original_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_price' => $pricing['final_price'],
            'discount_ends_at' => $discountEndsAt,
        ];

        if ($subscription) {
            $subscription->update($payload);
        } else {
            $subscription = $user->subscriptions()->create($payload);
        }

        if ($subscription->items()->count() === 0) {
            $subscription->items()->create([
                'stripe_id' => 'item_' . uniqid(),
                'stripe_product' => $planInterval->plan->stripe_product_id ?? 'product_' . $planInterval->plan_id,
                'stripe_price' => $stripePrice,
                'quantity' => 1,
            ]);
        }

        if ($coupon && $incrementCouponUses) {
            $coupon->incrementUses();
        }

        return $subscription;
    }

    private function renderSuccessResponse(User $user, Subscription $subscription, bool $localMode): Response
    {
        $planInterval = $this->resolvePlanIntervalForSubscription($subscription);
        $team = $user->currentTeam ?: $user->ownedTeams()->latest('id')->first();

        return Inertia::render('Subscriptions/Success', [
            'user' => $user,
            'subscription' => [
                'id' => $subscription->stripe_id,
                'plan_name' => $planInterval?->plan?->name ?? $subscription->type,
                'plan_interval_id' => $planInterval?->id,
                'price' => (float) ($planInterval?->price ?? $subscription->original_price ?? $subscription->final_price ?? 0),
                'interval' => $planInterval?->interval?->name ?? 'Mensal',
                'features' => $this->extractPlanFeatures($planInterval),
            ],
            'store' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'status' => $team->status,
                'is_featured' => (bool) $team->featured,
                'featured_until' => $team->featured_until?->format('d/m/Y'),
            ] : null,
            'local_mode' => $localMode,
        ]);
    }

    private function resolvePlanIntervalForSubscription(Subscription $subscription): ?PlanInterval
    {
        if ($subscription->stripe_price) {
            $byStripePrice = PlanInterval::query()
                ->with(['plan', 'interval'])
                ->where('stripe_price_id', $subscription->stripe_price)
                ->first();

            if ($byStripePrice) {
                return $byStripePrice;
            }

            if (preg_match('/^price_(\d+)$/', (string) $subscription->stripe_price, $matches) === 1) {
                return PlanInterval::query()
                    ->with(['plan', 'interval'])
                    ->whereKey((int) $matches[1])
                    ->first();
            }
        }

        return PlanInterval::query()
            ->with(['plan', 'interval'])
            ->whereHas('plan', function ($query) use ($subscription): void {
                $query->where('name', $subscription->type);
            })
            ->first();
    }

    private function isFormalizedLocalSubscription(?Subscription $subscription): bool
    {
        if (!$subscription) {
            return false;
        }

        $stripeReference = (string) $subscription->stripe_id;
        return str_starts_with($stripeReference, 'cs_') || str_starts_with($stripeReference, 'sub_');
    }

    private function findStripeManagedSubscription(User $user, StripeClient $stripe): ?\Stripe\Subscription
    {
        $user->createOrGetStripeCustomer();

        $customerId = (string) ($user->stripe_id ?? '');
        if ($customerId === '') {
            return null;
        }

        $list = $stripe->subscriptions->all([
            'customer' => $customerId,
            'status' => 'all',
            'limit' => 20,
        ]);

        $candidates = array_values(array_filter($list->data ?? [], static function ($subscription): bool {
            $status = (string) ($subscription->status ?? '');
            return in_array($status, ['active', 'trialing', 'past_due', 'unpaid'], true);
        }));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function ($left, $right): int {
            return ((int) ($right->created ?? 0)) <=> ((int) ($left->created ?? 0));
        });

        /** @var \Stripe\Subscription $subscription */
        $subscription = $candidates[0];
        return $subscription;
    }

    private function comparePlansForMigration(Plan $targetPlan, Plan $currentPlan): int
    {
        $sortDiff = ((int) ($targetPlan->sort_order ?? PHP_INT_MAX))
            <=> ((int) ($currentPlan->sort_order ?? PHP_INT_MAX));

        if ($sortDiff !== 0) {
            return $sortDiff;
        }

        $targetMin = $this->getPlanMinimumPrice($targetPlan);
        $currentMin = $this->getPlanMinimumPrice($currentPlan);

        return $targetMin <=> $currentMin;
    }

    private function getPlanMinimumPrice(Plan $plan): float
    {
        $min = $plan->intervals()
            ->selectRaw('MIN(plan_intervals.price) as min_price')
            ->value('min_price');

        return (float) ($min ?? 0);
    }

    /**
     * @return array<int, string>
     */
    private function extractPlanFeatures(?PlanInterval $planInterval): array
    {
        $rawFeatures = $planInterval?->plan?->features;
        if (!is_array($rawFeatures)) {
            return [];
        }

        $features = [];
        foreach ($rawFeatures as $key => $value) {
            if ($key === 'analytics') {
                continue;
            }

            if (is_string($value)) {
                $text = trim($value);
                if ($text !== '') {
                    $features[] = $text;
                }

                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $nestedValue) {
                if (!is_string($nestedValue)) {
                    continue;
                }

                $text = trim($nestedValue);
                if ($text !== '') {
                    $features[] = $text;
                }
            }
        }

        return array_values(array_unique($features));
    }

    private function buildTeamPlanPayload(PlanInterval $planInterval): array
    {
        $isFeaturedPlan = (bool) ($planInterval->plan?->is_featured ?? false);

        return [
            'plan_id' => $planInterval->plan_id,
            'status' => 'ativo',
            'featured' => $isFeaturedPlan,
            // Contratação ativa destaque sem prazo fixo.
            // O prazo passa a ser definido manualmente no admin quando necessário.
            'featured_until' => null,
        ];
    }
}
