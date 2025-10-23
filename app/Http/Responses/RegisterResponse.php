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
                // If email verification is required and not verified, redirect to verification
                if (config('fortify.features') && in_array('emailVerification', config('fortify.features')) && !$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice')->with('error', 'Invalid plan. Please verify your email first.');
                }
                return redirect()->route('dashboard')->with('error', 'Invalid plan');
            }

            $planName = $planInterval->plan->name . ' ' . $planInterval->interval->name;

            // Se estiver em ambiente local, criar assinatura local sem passar pelo Stripe
            if (app()->environment('local')) {
                \Log::info('Local environment detected - creating subscription without Stripe checkout', [
                    'user_id' => $user->id,
                    'plan_name' => $planName,
                    'plan_interval_id' => $planIntervalId,
                ]);

                try {
                    // Criar assinatura local
                    $subscription = $user->subscriptions()->create([
                        'type' => $planName,
                        'stripe_id' => 'local_' . uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $planInterval->stripe_price_id ?? 'local_price',
                        'quantity' => 1,
                        'trial_ends_at' => null,
                        'ends_at' => null,
                    ]);

                    // Criar item da assinatura
                    $subscription->items()->create([
                        'stripe_id' => 'local_item_' . uniqid(),
                        'stripe_product' => $planInterval->plan->stripe_product_id ?? 'local_product',
                        'stripe_price' => $planInterval->stripe_price_id ?? 'local_price',
                        'quantity' => 1,
                    ]);

                    // Atualizar plano do team
                    $user->currentTeam->update([
                        'plan_id' => $planInterval->plan_id,
                    ]);

                    \Log::info('Local subscription created successfully', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                    ]);

                    return redirect()->route('dashboard')->with('success', 'Assinatura criada com sucesso! (Ambiente Local)');

                } catch (\Exception $e) {
                    \Log::error('Error creating local subscription', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);

                    return redirect()->route('dashboard')->with('error', 'Erro ao criar assinatura local');
                }
            }

            // Ambiente de produção/staging - continuar com checkout Stripe
            if (!$planInterval->stripe_price_id) {
                return redirect()->route('dashboard')->with('error', 'Invalid plan - missing Stripe price ID');
            }

            try {
                if (!$user->stripe_id) {
                    $user->createAsStripeCustomer();
                }

                \Log::info('Creating checkout session for unverified user', [
                    'user_id' => $user->id,
                    'user_verified' => $user->hasVerifiedEmail(),
                    'plan_name' => $planInterval->plan->name,
                    'stripe_price_id' => $planInterval->stripe_price_id
                ]);

                // Store plan info in session to use after email verification
                session(['pending_plan_checkout' => [
                    'plan_interval_id' => $planIntervalId,
                    'plan_name' => $planName,
                ]]);

                $checkout = $user->newSubscription($planName, $planInterval->stripe_price_id)
                    ->allowPromotionCodes()
                    ->checkout([
                        'success_url' => route('onboarding.success').'?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('subscriptions.cancel'),
                        'customer_update' => [
                            'name' => 'auto',
                            'address' => 'auto',
                        ],
                        'line_items' => [
                            [
                                'price' => $planInterval->stripe_price_id,
                                'quantity' => 1,
                            ],
                            [
                                'price_data' => [
                                    'currency' => 'brl',
                                    'product_data' => [
                                        'name' => 'Taxa de Implementação',
                                        'description' => 'Configuração inicial da plataforma',
                                    ],
                                    'unit_amount' => 50000, // R$ 500,00 em centavos
                                ],
                                'quantity' => 1,
                            ],
                        ],
                        'metadata' => [
                            'user_id' => $user->id,
                            'plan_name' => $planName,
                            'plan_interval_id' => $planIntervalId,
                            'interval' => $planInterval->interval->name,
                            'requires_email_verification' => !$user->hasVerifiedEmail(),
                        ],
                        'subscription_data' => [
                            'metadata' => [
                                'user_id' => $user->id,
                                'plan_name' => $planName,
                                'plan_interval_id' => $planIntervalId,
                                'requires_email_verification' => !$user->hasVerifiedEmail(),
                            ]
                        ]
                    ]);

                \Log::info('Redirecting to checkout', [
                    'checkout_url' => $checkout->url,
                    'user_verified' => $user->hasVerifiedEmail(),
                ]);

                return Inertia::location($checkout->url);

            } catch (\Exception $e) {
                \Log::error('Error creating checkout session', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                // If email verification is required and not verified, redirect to verification
                if (config('fortify.features') && in_array('emailVerification', config('fortify.features')) && !$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice')->with('error', 'Error creating checkout session. Please verify your email first.');
                }

                return redirect()->route('dashboard')->with('error', 'Error creating checkout session');
            }
        }

        // Default behavior: check if email verification is required
        if (config('fortify.features') && in_array('emailVerification', config('fortify.features')) && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Retorno padrão para requisições sem plano
        return redirect()->route('dashboard');
    }
}
