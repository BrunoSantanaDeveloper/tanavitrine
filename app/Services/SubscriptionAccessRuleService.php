<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

final class SubscriptionAccessRuleService
{
    public const KEY_DEFAULT_TRIAL_DAYS = 'subscription_default_trial_days';

    public const KEY_HIDE_STORE_WHEN_EXPIRED = 'subscription_hide_store_when_expired';

    public const KEY_KEEP_PANEL_ACCESS_WHEN_EXPIRED = 'subscription_keep_panel_access_when_expired';

    public const KEY_ALLOW_PLAN_NEW_USER_DISCOUNTS = 'subscription_allow_plan_new_user_discounts';

    public function getDefaultTrialDays(): int
    {
        $value = Setting::getValueByKey(self::KEY_DEFAULT_TRIAL_DAYS, 14);

        return max(0, (int) $value);
    }

    public function shouldHideStoreWhenExpired(): bool
    {
        return $this->toBool(
            Setting::getValueByKey(self::KEY_HIDE_STORE_WHEN_EXPIRED, true),
            true
        );
    }

    public function shouldKeepPanelAccessWhenExpired(): bool
    {
        return $this->toBool(
            Setting::getValueByKey(self::KEY_KEEP_PANEL_ACCESS_WHEN_EXPIRED, true),
            true
        );
    }

    public function shouldAllowPlanNewUserDiscounts(): bool
    {
        return $this->toBool(
            Setting::getValueByKey(self::KEY_ALLOW_PLAN_NEW_USER_DISCOUNTS, false),
            false
        );
    }

    public function getRules(): array
    {
        return [
            'default_trial_days' => $this->getDefaultTrialDays(),
            'hide_store_when_expired' => $this->shouldHideStoreWhenExpired(),
            'keep_panel_access_when_expired' => $this->shouldKeepPanelAccessWhenExpired(),
            'allow_plan_new_user_discounts' => $this->shouldAllowPlanNewUserDiscounts(),
        ];
    }

    /**
     * Check if a subscription still grants storefront access.
     *
     * Rules:
     * - If status is not active/trialing, access is expired.
     * - If ends_at exists and is in the past, access is expired.
     * - If trial_ends_at is null, treat as active ongoing subscription.
     * - If trial expired, discount_ends_at can extend access.
     */
    public function hasActiveAccess(?Subscription $subscription): bool
    {
        if (!$subscription) {
            return false;
        }

        if (!in_array($subscription->stripe_status, ['active', 'trialing'], true)) {
            return false;
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            return false;
        }

        if ($subscription->trial_ends_at === null) {
            return true;
        }

        if ($subscription->trial_ends_at->isFuture()) {
            return true;
        }

        return $subscription->discount_ends_at && $subscription->discount_ends_at->isFuture();
    }

    /**
     * Build a single normalized payload for trial/active metadata.
     * This avoids repeating the same mapping across controllers.
     */
    public function buildSubscriptionMeta(Subscription $subscription): array
    {
        $isTrial = $subscription->onTrial();
        $trialEndsAt = $subscription->trial_ends_at;
        $hasActiveDiscount = $subscription->hasActiveDiscount();
        $discountEndsAt = $subscription->discount_ends_at;
        $hasActiveAccess = $this->hasActiveAccess($subscription);
        $stripeReference = (string) $subscription->stripe_id;
        $isFormalized = str_starts_with($stripeReference, 'cs_')
            || str_starts_with($stripeReference, 'sub_');

        return [
            'status' => $subscription->stripe_status,
            'is_trial' => $isTrial,
            'trial_ends_at' => $trialEndsAt ? $trialEndsAt->format('d/m/Y') : null,
            'trial_days_remaining' => $isTrial && $trialEndsAt
                ? (int) now()->diffInDays($trialEndsAt, false)
                : null,
            'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : null,
            'is_active' => $subscription->active(),
            'has_active_access' => $hasActiveAccess,
            'access_status' => $hasActiveAccess ? 'active' : 'expired',
            'on_grace_period' => $subscription->onGracePeriod(),
            'is_formalized' => $isFormalized,
            'has_active_discount' => $hasActiveDiscount,
            'discount_ends_at' => $discountEndsAt ? $discountEndsAt->format('d/m/Y') : null,
            'discount_days_remaining' => $hasActiveDiscount ? $subscription->getDiscountDaysRemaining() : null,
        ];
    }

    /**
     * Account deletion is allowed only when there is no formalized subscription
     * currently granting active access.
     *
     * @return array{
     *   can_delete_account: bool,
     *   message: string|null,
     *   blocked_until: string|null
     * }
     */
    public function getAccountDeletionGuard(?User $user): array
    {
        if (!$user) {
            return [
                'can_delete_account' => false,
                'message' => 'Não foi possível validar sua conta no momento.',
                'blocked_until' => null,
            ];
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->subscriptions()
            ->where('name', 'default')
            ->latest()
            ->first();

        if (!$subscription) {
            return [
                'can_delete_account' => true,
                'message' => null,
                'blocked_until' => null,
            ];
        }

        $meta = $this->buildSubscriptionMeta($subscription);
        $hasActiveAccess = (bool) ($meta['has_active_access'] ?? false);
        $isFormalized = (bool) ($meta['is_formalized'] ?? false);

        if (!$hasActiveAccess || !$isFormalized) {
            return [
                'can_delete_account' => true,
                'message' => null,
                'blocked_until' => null,
            ];
        }

        $blockedUntil = $subscription->ends_at?->format('d/m/Y');

        if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
            return [
                'can_delete_account' => false,
                'message' => "Seu plano está em cancelamento agendado e segue ativo até {$blockedUntil}. Após essa data, você poderá excluir a conta.",
                'blocked_until' => $blockedUntil,
            ];
        }

        return [
            'can_delete_account' => false,
            'message' => 'Para excluir sua conta, primeiro cancele o plano em "Planos e Cobrança".',
            'blocked_until' => null,
        ];
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        }

        return $default;
    }
}
