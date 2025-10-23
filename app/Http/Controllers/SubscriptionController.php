<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

final class SubscriptionController extends Controller
{
    /**
     * Redirect authenticated user to Stripe billing portal.
     */
    public function index(): RedirectResponse
    {
        if (! Config::get('cashier.billing_enabled')) {
            return redirect()->route('dashboard');
        }

        // In development, redirect directly to subscriptions page if portal is not configured
        if (app()->environment('local', 'development')) {
            try {
                return type(Auth::user())->as(User::class)->redirectToBillingPortal(route('subscriptions.create'));
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                if (str_contains($e->getMessage(), 'No configuration provided')) {
                    return redirect()->route('subscriptions.create')
                        ->with('info', 'Modo desenvolvimento: Gerenciamento de assinaturas disponível aqui.');
                }
                throw $e;
            }
        }

        return type(Auth::user())->as(User::class)->redirectToBillingPortal(route('subscriptions.create'));
    }

    /**
     * Display subscription management page with active subscriptions and available plans.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! Config::get('cashier.billing_enabled')) {
            return redirect()->route('dashboard');
        }

        /** @var User $user */
        $user = $request->user();

        /** @var Collection<int, Subscription> $activeSubscriptions */
        $activeSubscriptions = Subscription::query()->where(['user_id' => $user->id])->active()->get();

        // Get team and current plan
        $team = $user->currentTeam;
        $currentPlan = null;
        $limits = [];
        $usage = [];

        if ($team && $team->plan_id) {
            // Load the plan with its intervals and limits
            $currentPlan = Plan::with(['intervals', 'limits'])->find($team->plan_id);

            if ($currentPlan) {
                // Get the active subscription to determine price and interval
                $activeSubscription = $user->subscriptions()
                    ->where('stripe_status', 'active')
                    ->latest()
                    ->first();

                // Add price and interval info to currentPlan
                if ($activeSubscription && $currentPlan->intervals->isNotEmpty()) {
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

                $limits = $currentPlan->limits->pluck('limit', 'resource')->toArray();

                // Calculate current usage
                $usage = [

                ];
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
     * Create Stripe Checkout session for selected subscription plan.
     *
     * @throws NotFoundHttpException If subscription plan not found
     */
    public function show(string $subscription): Checkout|RedirectResponse
    {
        if (! Config::get('cashier.billing_enabled')) {
            return redirect()->route('dashboard');
        }

        /** @var array<int, array<string, mixed>> $subscriptionConfig */
        $subscriptionConfig = Config::array('subscriptions.subscriptions');
        $subscriptions = collect($subscriptionConfig);

        abort_unless(
            in_array($subscription, $subscriptions->pluck('price_id')->toArray()),
            404
        );

        /** @var User $user */
        $user = request()->user();

        /** @var array<string, mixed>|null $subscriptionData */
        $subscriptionData = $subscriptions->firstWhere('price_id', $subscription);

        abort_if($subscriptionData === null, 404);

        $name = type(Arr::get($subscriptionData, 'plan'))->asString();

        return $user
            ->newSubscription($name, $subscription)
            ->checkout([
                'success_url' => route('subscriptions.index'),
                'cancel_url' => route('subscriptions.create'),
            ]);
    }

    /**
     * Create Stripe Checkout session for selected plan interval.
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
                    ->with('error', 'Please login to continue.');
            }

            // Get plan interval from database directly
            $planInterval = DB::table('plan_intervals')
                ->join('plans', 'plan_intervals.plan_id', '=', 'plans.id')
                ->join('intervals', 'plan_intervals.interval_id', '=', 'intervals.id')
                ->where('plan_intervals.id', $plan_interval_id)
                ->select(
                    'plans.id as plan_id',
                    'plans.name as plan_name',
                    'plan_intervals.id as plan_interval_id',
                    'plan_intervals.stripe_price_id',
                    'intervals.name as interval_name'
                )
                ->first();

            if (!$planInterval) {
                \Log::error('Plan interval not found', [
                    'plan_interval_id' => $plan_interval_id
                ]);
                throw new \Exception('Plan interval not found');
            }

            // LOCAL MODE: Skip Stripe and create subscription directly
            if (app()->environment('local', 'development')) {
                \Log::info('LOCAL MODE: Creating subscription without Stripe', [
                    'user_id' => $user->id,
                    'plan_name' => $planInterval->plan_name,
                ]);

                // Create or update team with plan
                $team = $user->currentTeam;
                if ($team) {
                    $team->plan_id = $planInterval->plan_id;
                    $team->save();
                }

                // Create local subscription without Stripe
                $subscription = $user->subscriptions()->create([
                    'type' => $planInterval->plan_name,
                    'stripe_id' => 'local_sub_' . uniqid(),
                    'stripe_status' => 'active',
                    'stripe_price' => $planInterval->stripe_price_id ?? 'local_price',
                    'quantity' => 1,
                    'trial_ends_at' => null,
                    'ends_at' => null,
                ]);

                \Log::info('LOCAL MODE: Subscription created', [
                    'subscription_id' => $subscription->id,
                ]);

                // Verify email if not verified (skip in local mode)
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                return redirect()->route('subscriptions.success', [
                    'session_id' => 'local_' . $subscription->id,
                    'mode' => 'local'
                ]);
            }

            // PRODUCTION MODE: Use Stripe
            // Ensure user has a Stripe customer ID
            if (!$user->stripe_id) {
                \Log::info('Creating Stripe customer for user', ['user_id' => $user->id]);
                $user->createAsStripeCustomer();
            }

            \Log::info('User details', [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
                'email' => $user->email,
                'has_verified_email' => $user->hasVerifiedEmail(),
            ]);

            $stripe_price_id = $planInterval->stripe_price_id;

            if (!$stripe_price_id) {
                \Log::error('Stripe price ID not found', [
                    'plan_interval_id' => $plan_interval_id
                ]);
                throw new \Exception('Stripe price ID not found for this plan interval');
            }

            \Log::info('Creating checkout session', [
                'user_id' => $user->id,
                'plan_name' => $planInterval->plan_name,
                'stripe_price_id' => $stripe_price_id
            ]);

            $successUrl = route('subscriptions.success').'?session_id={CHECKOUT_SESSION_ID}';

            \Log::info('Success URL', [
                'success_url' => $successUrl
            ]);

            // Create checkout session
            $checkout = $user->newSubscription($planInterval->plan_name, $stripe_price_id)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => $successUrl,
                    'cancel_url' => route('subscriptions.create'),
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_name' => $planInterval->plan_name,
                        'stripe_price_id' => $stripe_price_id,
                        'plan_interval_id' => $plan_interval_id,
                    ],
                    'subscription_data' => [
                        'metadata' => [
                            'user_id' => $user->id,
                            'plan_name' => $planInterval->plan_name,
                            'stripe_price_id' => $stripe_price_id,
                            'plan_interval_id' => $plan_interval_id,
                        ]
                    ]
                ]);

            \Log::info('Checkout URL', [
                'checkout_url' => $checkout->url
            ]);

            // Redirect manually to the checkout URL
            return response()->json(['checkout_url' => $checkout->url]);

        } catch (\Exception $e) {
            \Log::error('Error creating checkout session', [
                'error' => $e->getMessage(),
                'plan_interval_id' => $plan_interval_id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Unable to create checkout session. Please try again.');
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $mode = $request->get('mode');
        $user = auth()->user();

        \Log::info('Success request', [
            'session_id' => $sessionId,
            'mode' => $mode,
            'user_id' => $user->id,
            'user_verified' => $user->hasVerifiedEmail(),
        ]);

        // LOCAL MODE: Handle local subscription
        if ($mode === 'local' || str_starts_with($sessionId, 'local_')) {
            \Log::info('LOCAL MODE: Processing local subscription success');

            $subscriptionId = str_replace('local_', '', $sessionId);
            $subscription = $user->subscriptions()->find($subscriptionId);

            if (!$subscription) {
                \Log::error('LOCAL MODE: Subscription not found', [
                    'subscription_id' => $subscriptionId
                ]);
                return redirect()->route('dashboard')->with('error', 'Subscription not found');
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

        // PRODUCTION MODE: Handle Stripe subscription
        try {
            Stripe::setApiKey(config('cashier.secret'));

            $session = StripeSession::retrieve($sessionId);

            if ($session && isset($session->subscription)) {
                \Log::info('Subscription found', [
                    'session_id' => $sessionId,
                    'subscription' => $session->subscription,
                    'requires_email_verification' => $session->metadata->requires_email_verification ?? false,
                ]);

                // Verificar metadados tanto na sessão quanto nos dados da assinatura
                $planName = $session->metadata->plan_name ?? $session->subscription_data->metadata->plan_name ?? null;
                $planIntervalId = $session->metadata->plan_interval_id ?? $session->subscription_data->metadata->plan_interval_id ?? null;
                $requiresEmailVerification = $session->metadata->requires_email_verification ?? false;

                if (!$planName || !$planIntervalId) {
                    \Log::error('Missing plan information in session metadata', [
                        'session_id' => $sessionId,
                        'session_metadata' => $session->metadata,
                        'subscription_data' => $session->subscription_data ?? null
                    ]);
                    throw new \Exception('Missing plan information');
                }

                // Clear pending checkout from session
                session()->forget('pending_plan_checkout');

                // Render success page to collect delivery address
                return Inertia::render('Onboarding/OnboardingSuccess', [
                    'user' => $user,
                    'subscription' => [
                        'id' => $session->subscription,
                        'plan_name' => $planName,
                        'plan_interval_id' => $planIntervalId,
                    ],
                    'clinic_address' => $user->currentTeam?->address ?? null,
                ]);
            }

            \Log::error('Stripe session not found or missing subscription', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
            ]);

            return redirect()->route('dashboard')->with('error', 'Subscription not found');

        } catch (\Exception $e) {
            \Log::error('Error creating subscription', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $user->id,
            ]);
            return redirect()->route('dashboard')->with('error', 'Error retrieving subscription');
        }
    }

    public function cancel(): RedirectResponse
    {
        session()->forget('checkout');

        return redirect()->route('dashboard')->with('success', 'Subscription cancelled successfully');
    }
}
