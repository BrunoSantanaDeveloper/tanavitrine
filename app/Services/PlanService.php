<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Interval;
use Laravel\Cashier\Cashier;
use Stripe\Stripe;

class PlanService
{
    public function createStripeProduct(Plan $plan): void
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            // Create Stripe product
            $stripeProduct = $stripe->products->create([
                'name' => $plan->name,
                'description' => $plan->description,
                'metadata' => [
                    'features' => json_encode($plan->features),
                    'is_default' => $plan->is_default,
                ],
            ]);

            // Create Stripe price
            $stripePrice = $stripe->prices->create([
                'unit_amount' => $plan->price * 100, // Convert to cents
                'currency' => $plan->currency,
                'product' => $stripeProduct->id,
                'recurring' => [
                    'interval' => $plan->interval,
                ],
            ]);

            // Update plan with Stripe IDs
            $plan->update([
                'stripe_product_id' => $stripeProduct->id,
                'stripe_price_id' => $stripePrice->id,
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Error creating Stripe product/price: ' . $e->getMessage());
        }
    }

    public function updateStripeProduct(Plan $plan): void
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            // Update or create Stripe product
            if ($plan->stripe_product_id) {
                $stripe->products->update($plan->stripe_product_id, [
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'features' => json_encode($plan->features),
                        'is_default' => $plan->is_default,
                    ],
                ]);
            } else {
                $stripeProduct = $stripe->products->create([
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'features' => json_encode($plan->features),
                        'is_default' => $plan->is_default,
                    ],
                ]);
                $plan->stripe_product_id = $stripeProduct->id;
            }

            // Create new price (Stripe doesn't allow updating prices)
            $stripePrice = $stripe->prices->create([
                'unit_amount' => $plan->price * 100,
                'currency' => $plan->currency,
                'product' => $plan->stripe_product_id,
                'recurring' => [
                    'interval' => $plan->interval,
                ],
            ]);

            // Update plan with new price ID
            $plan->update([
                'stripe_price_id' => $stripePrice->id,
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Error updating Stripe product/price: ' . $e->getMessage());
        }
    }

    public function createPlan(array $data): Plan
    {
        try {
            if (!config('cashier.secret')) {
                throw new \Exception('Stripe API key is not configured. Please set STRIPE_KEY in your .env file.');
            }

            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            // Create Stripe product
            $stripeProduct = $stripe->products->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'metadata' => [
                    'features' => json_encode($data['features'] ?? []),
                    'is_default' => $data['is_default'] ?? false,
                ],
            ]);

            // Create plan
            $plan = Plan::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'stripe_product_id' => $stripeProduct->id,
                'currency' => $data['currency'],
                'features' => $data['features'] ?? [],
                'is_featured' => $data['is_featured'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
                'trial_days' => $data['trial_days'] ?? null,
            ]);

            \Log::info('Plan created: ' . $plan->id);
            \Log::info('PlanService Data: ' . json_encode($data));

            // Create Stripe prices for each interval
            if (isset($data['intervals']) && is_array($data['intervals'])) {
                foreach ($data['intervals'] as $intervalData) {
                    if (!isset($intervalData['interval_id']) || !isset($intervalData['price'])) {
                        continue;
                    }

                    // Buscar o intervalo pelo ID para obter o código
                    $interval = Interval::findOrFail($intervalData['interval_id']);

                    try {
                        // Criar preço no Stripe
                        $stripePrice = $stripe->prices->create([
                            'unit_amount' => (int)($intervalData['price'] * 100),
                            'currency' => $data['currency'],
                            'product' => $stripeProduct->id,
                            'recurring' => [
                                'interval' => $interval->code,
                                'trial_period_days' => $data['trial_days'] ?? null,
                            ],

                        ]);

                        // Anexar o intervalo ao plano usando attach
                        $plan->intervals()->attach($interval->id, [
                            'price' => $intervalData['price'],
                            'stripe_price_id' => $stripePrice->id,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating Stripe price for interval: ' . $e->getMessage());
                        continue;
                    }
                }
            }

            // Create limits
            if (isset($data['limits']) && is_array($data['limits'])) {
                foreach ($data['limits'] as $limitData) {
                    if (!isset($limitData['module']) || !isset($limitData['resource'])) {
                        continue;
                    }

                    $plan->limits()->create([
                        'module' => $limitData['module'],
                        'resource' => $limitData['resource'],
                        'limit_type' => $limitData['limit_type'],
                        'limit_value' => $limitData['limit_value'],
                        'period' => $limitData['period'] ?? 'month',
                        'grace_period_days' => $limitData['grace_period_days'] ?? null,
                        'notification_threshold' => $limitData['notification_threshold'] ?? 80,
                        'is_hard_limit' => $limitData['is_hard_limit'] ?? true,
                        'notify_on_limit' => $limitData['notify_on_limit'] ?? true,
                        'metadata' => $limitData['metadata'] ?? [],
                    ]);
                }
            }

            return $plan;

        } catch (\Exception $e) {
            // Se houver erro, tentar limpar o produto do Stripe se foi criado
            if (isset($stripeProduct)) {
                try {
                    $stripe->products->delete($stripeProduct->id);
                } catch (\Exception $deleteError) {
                    // Ignorar erro ao tentar deletar o produto
                }
            }
            throw new \Exception('Error creating plan: ' . $e->getMessage());
        }
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {

        \Log::info('UpdatePlan Data: ' . json_encode($data));
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            // Update Stripe product
            $stripe->products->update($plan->stripe_product_id, [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'metadata' => [
                    'features' => json_encode($data['features'] ?? []),
                    'is_default' => $data['is_default'] ?? false,
                ],
            ]);

            // Update plan
            $plan->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'currency' => $data['currency'],
                'features' => $data['features'] ?? [],
                'is_featured' => $data['is_featured'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
            ]);

            \Log::info('UpdatePlan Plan: ' . json_encode($plan));

            // Update intervals
            if (isset($data['intervals'])) {
                // Buscar os intervalos antigos ANTES do detach
                $oldIntervals = $plan->intervals()->get()->keyBy('id');

                // Remove old intervals
                $plan->intervals()->detach();

                // Add new intervals
                foreach ($data['intervals'] as $intervalData) {
                    $interval = Interval::findOrFail($intervalData['interval_id']);
                    $existing = $oldIntervals->get($interval->id);

                    if ($existing && $existing->pivot->price == $intervalData['price']) {
                        // Se o preço não mudou, reanexa o intervalo com o mesmo stripe_price_id
                        $plan->intervals()->attach($interval->id, [
                            'price' => $intervalData['price'],
                            'stripe_price_id' => $existing->pivot->stripe_price_id,
                        ]);
                    } else {
                        // Se mudou, cria um novo price no Stripe
                        $stripePrice = $stripe->prices->create([
                            'unit_amount' => $intervalData['price'] * 100,
                            'currency' => $data['currency'],
                            'product' => $plan->stripe_product_id,
                            'recurring' => [
                                'interval' => $interval->code,
                            ],
                        ]);
                        $plan->intervals()->attach($interval->id, [
                            'price' => $intervalData['price'],
                            'stripe_price_id' => $stripePrice->id,
                        ]);
                    }
                }
            }

            return $plan;

        } catch (\Exception $e) {
            throw new \Exception('Error updating plan: ' . $e->getMessage());
        }
    }

    public function handleTrialConversion(string $subscriptionId): void
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            $subscription = $stripe->subscriptions->retrieve($subscriptionId);

            if ($subscription->status === 'trialing') {
                // Get the default plan
                $defaultPlan = Plan::where('is_default', true)->first();

                if ($defaultPlan) {
                    // Update subscription to default plan
                    $stripe->subscriptions->update($subscriptionId, [
                        'items' => [
                            [
                                'id' => $subscription->items->data[0]->id,
                                'price' => $defaultPlan->stripe_price_id,
                            ],
                        ],
                    ]);

                    // Update trial conversions count
                    $plan = Plan::where('stripe_product_id', $subscription->plan->product)->first();
                    if ($plan) {
                        $plan->increment('trial_conversions');
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error handling trial conversion: ' . $e->getMessage());
        }
    }

    public function createDiscountCoupon(array $data): array
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            $coupon = $stripe->coupons->create([
                'name' => $data['name'],
                'duration' => $data['duration'] ?? 'once',
                'duration_in_months' => $data['duration_in_months'] ?? null,
                'percent_off' => $data['percent_off'] ?? null,
                'amount_off' => $data['amount_off'] ? $data['amount_off'] * 100 : null,
                'currency' => $data['currency'] ?? 'usd',
                'max_redemptions' => $data['max_redemptions'] ?? null,
                'redeem_by' => $data['redeem_by'] ? strtotime($data['redeem_by']) : null,
            ]);

            return [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'percent_off' => $coupon->percent_off,
                'amount_off' => $coupon->amount_off,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'max_redemptions' => $coupon->max_redemptions,
                'redeem_by' => $coupon->redeem_by ? date('Y-m-d', $coupon->redeem_by) : null,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error creating discount coupon: ' . $e->getMessage());
        }
    }

    public function handleSubscriptionExpiration(string $subscriptionId): void
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));
            $stripe = Cashier::stripe();

            $subscription = $stripe->subscriptions->retrieve($subscriptionId);

            if ($subscription->status === 'canceled' || $subscription->status === 'unpaid') {
                // Get the default plan
                $defaultPlan = Plan::where('is_default', true)->first();

                if ($defaultPlan) {
                    // Create new subscription with default plan
                    $stripe->subscriptions->create([
                        'customer' => $subscription->customer,
                        'items' => [
                            [
                                'price' => $defaultPlan->stripe_price_id,
                            ],
                        ],
                        'payment_behavior' => 'default_incomplete',
                        'expand' => ['latest_invoice.payment_intent'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error handling subscription expiration: ' . $e->getMessage());
        }
    }
}
