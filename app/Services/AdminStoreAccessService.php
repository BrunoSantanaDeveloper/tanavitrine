<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminStoreAccessService
{
    /**
     * Grant extra access days for a store owner subscription.
     * Also reactivates storefront when it is currently inactive.
     *
     * @return array{
     *   subscription_id:int,
     *   created_subscription:bool,
     *   store_reactivated:bool,
     *   trial_ends_at:?string,
     *   discount_ends_at:?string
     * }
     */
    public function grantDays(Team $store, int $days): array
    {
        if ($days < 1) {
            throw new RuntimeException('Informe uma quantidade válida de dias.');
        }

        $owner = $store->owner;
        if (!$owner instanceof User) {
            throw new RuntimeException('Esta loja não possui proprietário válido.');
        }

        return DB::transaction(function () use ($store, $owner, $days): array {
            $subscription = $owner->subscriptions()
                ->where('name', 'default')
                ->orderByDesc('created_at')
                ->first();

            $createdSubscription = false;

            if (!$subscription instanceof Subscription) {
                $subscription = $this->createFallbackSubscription($owner, $store, $days);
                $createdSubscription = true;
            } else {
                $this->extendSubscription($subscription, $days);
            }

            $storeReactivated = false;
            if ($store->status === 'inativo') {
                $store->update(['status' => 'ativo']);
                $storeReactivated = true;
            }

            return [
                'subscription_id' => (int) $subscription->id,
                'created_subscription' => $createdSubscription,
                'store_reactivated' => $storeReactivated,
                'trial_ends_at' => $subscription->trial_ends_at?->format('d/m/Y'),
                'discount_ends_at' => $subscription->discount_ends_at?->format('d/m/Y'),
            ];
        });
    }

    private function extendSubscription(Subscription $subscription, int $days): void
    {
        if ($subscription->trial_ends_at !== null) {
            $baseDate = $this->resolveBaseDate(
                $subscription->trial_ends_at,
                $subscription->discount_ends_at,
                now()
            );

            $subscription->trial_ends_at = $baseDate->addDays($days);
        } else {
            $baseDate = $this->resolveBaseDate($subscription->discount_ends_at, now());
            $extendedEndsAt = $baseDate->addDays($days);

            if ($subscription->discount_ends_at !== null) {
                $subscription->discount_ends_at = $extendedEndsAt;
            } else {
                // Quando não existe nenhuma data-limite, transforma em acesso temporário.
                $subscription->trial_ends_at = $extendedEndsAt;
            }
        }

        $subscription->stripe_status = 'active';
        $subscription->ends_at = null;
        $subscription->save();
    }

    private function createFallbackSubscription(User $owner, Team $store, int $days): Subscription
    {
        $plan = $store->plan;
        if (!$plan instanceof Plan) {
            $plan = Plan::query()
                ->where('is_active', true)
                ->where('is_default', false)
                ->with('intervals')
                ->orderBy('sort_order')
                ->first();
        } else {
            $plan->loadMissing('intervals');
        }

        $interval = $plan?->intervals
            ?->sortBy(fn ($interval): float => (float) ($interval->pivot->price ?? 0))
            ->first();

        $stripePrice = (string) ($interval?->pivot?->stripe_price_id ?? ('price_admin_' . ($plan?->id ?? 'manual')));
        $basePrice = (float) ($interval?->pivot?->price ?? 0);

        $subscription = $owner->subscriptions()->create([
            'name' => 'default',
            'type' => $plan?->name ? $plan->name . ' Manual' : 'Acesso Manual',
            'stripe_id' => 'admin_grant_' . uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => $stripePrice,
            'quantity' => 1,
            'trial_ends_at' => now()->addDays($days),
            'ends_at' => null,
            'coupon_id' => null,
            'original_price' => $basePrice,
            'discount_amount' => 0,
            'final_price' => $basePrice,
            'discount_ends_at' => null,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'item_admin_' . uniqid(),
            'stripe_product' => (string) ($plan?->stripe_product_id ?? ('product_admin_' . ($plan?->id ?? 'manual'))),
            'stripe_price' => $stripePrice,
            'quantity' => 1,
        ]);

        if (!$store->plan_id && $plan instanceof Plan) {
            $store->update(['plan_id' => $plan->id]);
        }

        return $subscription;
    }

    private function resolveBaseDate(?CarbonInterface ...$dates): CarbonInterface
    {
        $base = now();

        foreach ($dates as $date) {
            if ($date instanceof CarbonInterface && $date->isFuture() && $date->greaterThan($base)) {
                $base = $date;
            }
        }

        return $base;
    }
}
