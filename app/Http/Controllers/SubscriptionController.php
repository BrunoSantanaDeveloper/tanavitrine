<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

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

        // Buscar subscription ativa do usuário (independente do team)
        $activeSubscription = $user->subscriptions()
            ->where('stripe_status', 'active')
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

                // Add subscription trial information
                $isTrial = $activeSubscription->onTrial();
                $trialEndsAt = $activeSubscription->trial_ends_at;

                $currentPlan->subscription = [
                    'status' => $activeSubscription->stripe_status,
                    'is_trial' => $isTrial,
                    'trial_ends_at' => $trialEndsAt ? $trialEndsAt->format('d/m/Y') : null,
                    'trial_days_remaining' => $isTrial && $trialEndsAt ? (int) now()->diffInDays($trialEndsAt, false) : null,
                    'ends_at' => $activeSubscription->ends_at ? $activeSubscription->ends_at->format('d/m/Y') : null,
                    'is_active' => $activeSubscription->active(),
                    'on_grace_period' => $activeSubscription->onGracePeriod(),
                ];

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
     * Create subscription for selected plan interval.
     */
    public function checkout(string $plan_interval_id)
    {
        \Log::info('Checkout attempt', [
            'plan_interval_id' => $plan_interval_id,
            'user_id' => auth()->id(),
        ]);

        try {
            /** @var User $user */
            $user = auth()->user();

            if (!$user) {
                \Log::error('User not authenticated');
                return redirect()
                    ->route('login')
                    ->with('error', 'Por favor, faça login para continuar.');
            }

            // Get plan interval from database
            $planInterval = DB::table('plan_intervals')
                ->join('plans', 'plan_intervals.plan_id', '=', 'plans.id')
                ->join('intervals', 'plan_intervals.interval_id', '=', 'intervals.id')
                ->where('plan_intervals.id', $plan_interval_id)
                ->select(
                    'plans.id as plan_id',
                    'plans.name as plan_name',
                    'plan_intervals.id as plan_interval_id',
                    'plan_intervals.price',
                    'intervals.name as interval_name'
                )
                ->first();

            if (!$planInterval) {
                \Log::error('Plan interval not found', [
                    'plan_interval_id' => $plan_interval_id
                ]);
                throw new \Exception('Plano não encontrado');
            }

            \Log::info('Creating subscription without Stripe', [
                'user_id' => $user->id,
                'plan_name' => $planInterval->plan_name,
            ]);

            // Create or update team with plan
            $team = $user->currentTeam;
            if ($team) {
                $team->plan_id = $planInterval->plan_id;
                $team->save();
            }

            // Create subscription without Stripe
            $subscription = $user->subscriptions()->create([
                'type' => $planInterval->plan_name,
                'stripe_id' => 'sub_' . uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => 'price_' . $plan_interval_id,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);

            \Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
            ]);

            // Verify email if not verified
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return redirect()->route('subscriptions.success', [
                'session_id' => 'local_' . $subscription->id,
                'mode' => 'local'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating subscription', [
                'error' => $e->getMessage(),
                'plan_interval_id' => $plan_interval_id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Não foi possível criar a assinatura. Por favor, tente novamente.');
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $user = auth()->user();

        \Log::info('Success request', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'user_verified' => $user->hasVerifiedEmail(),
        ]);

        $subscriptionId = str_replace('local_', '', $sessionId);
        $subscription = $user->subscriptions()->find($subscriptionId);

        if (!$subscription) {
            \Log::error('Subscription not found', [
                'subscription_id' => $subscriptionId
            ]);
            return redirect()->route('dashboard')->with('error', 'Assinatura não encontrada');
        }

        // Get plan info with price and interval
        $planInterval = DB::table('plan_intervals')
            ->join('plans', 'plan_intervals.plan_id', '=', 'plans.id')
            ->join('intervals', 'plan_intervals.interval_id', '=', 'intervals.id')
            ->where('plans.name', $subscription->type)
            ->select(
                'plans.name as plan_name',
                'plan_intervals.id as plan_interval_id',
                'plan_intervals.price',
                'intervals.name as interval_name'
            )
            ->first();

        return Inertia::render('Onboarding/OnboardingSuccess', [
            'user' => $user,
            'subscription' => [
                'id' => $subscription->stripe_id,
                'plan_name' => $planInterval ? $planInterval->plan_name : $subscription->type,
                'plan_interval_id' => $planInterval ? $planInterval->plan_interval_id : null,
                'price' => $planInterval && $planInterval->price ? floatval($planInterval->price) : 0,
                'interval' => $planInterval && $planInterval->interval_name ? $planInterval->interval_name : 'Mensal',
            ],
            'clinic_address' => $user->currentTeam?->address ?? null,
            'local_mode' => true,
        ]);
    }

    public function cancel(): RedirectResponse
    {
        session()->forget('checkout');

        return redirect()->route('dashboard')->with('success', 'Subscription cancelled successfully');
    }
}
