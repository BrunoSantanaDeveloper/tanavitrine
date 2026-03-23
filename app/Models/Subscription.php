<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected $fillable = [
        'user_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'coupon_id',
        'original_price',
        'discount_amount',
        'final_price',
        'discount_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'discount_ends_at' => 'datetime',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    /**
     * Get the coupon associated with this subscription
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Check if subscription has an active discount
     */
    public function hasActiveDiscount(): bool
    {
        if (!$this->coupon_id || !$this->discount_ends_at) {
            return false;
        }

        return now()->isBefore($this->discount_ends_at);
    }

    /**
     * Get current price (with or without discount)
     */
    public function getCurrentPrice(): float
    {
        if ($this->hasActiveDiscount() && $this->final_price !== null) {
            return (float) $this->final_price;
        }

        return (float) ($this->original_price ?? 0);
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage(): ?float
    {
        if (!$this->original_price || !$this->discount_amount) {
            return null;
        }

        return ($this->discount_amount / $this->original_price) * 100;
    }

    /**
     * Apply custom coupon to subscription
     * Renamed to avoid conflict with Laravel Cashier's applyCoupon method
     */
    public function applyCustomCoupon(Coupon $coupon, float $price): void
    {
        if (!$coupon->isValid()) {
            throw new \Exception('Cupom inválido ou expirado');
        }

        $discountAmount = $coupon->calculateDiscount($price);

        // Calculate expiration based on duration or use valid_until
        $discountEndsAt = $coupon->calculateExpirationDate();

        $this->update([
            'coupon_id' => $coupon->id,
            'original_price' => $price,
            'discount_amount' => $discountAmount,
            'final_price' => $price - $discountAmount,
            'discount_ends_at' => $discountEndsAt,
        ]);

        $coupon->incrementUses();
    }

    /**
     * Remove coupon from subscription
     */
    public function removeCoupon(): void
    {
        $this->update([
            'coupon_id' => null,
            'original_price' => null,
            'discount_amount' => null,
            'final_price' => null,
            'discount_ends_at' => null,
        ]);
    }

    /**
     * Get days remaining for discount
     */
    public function getDiscountDaysRemaining(): ?int
    {
        if (!$this->hasActiveDiscount()) {
            return null;
        }

        return (int) now()->diffInDays($this->discount_ends_at);
    }
}
