<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'partner_id',
        'name',
        'description',
        'type',
        'value',
        'max_uses',
        'uses_count',
        'valid_from',
        'valid_until',
        'duration_value',
        'duration_unit',
        'is_active',
        'is_exit_intent',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'duration_value' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_exit_intent' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Calculate expiration date based on duration
     */
    public function calculateExpirationDate(\DateTimeInterface $startDate = null): ?\Carbon\Carbon
    {
        if (!$this->duration_value || !$this->duration_unit) {
            return $this->valid_until ? \Carbon\Carbon::parse($this->valid_until) : null;
        }

        $start = $startDate ? \Carbon\Carbon::parse($startDate) : \Carbon\Carbon::now();

        return match($this->duration_unit) {
            'days' => $start->copy()->addDays($this->duration_value),
            'months' => $start->copy()->addMonths($this->duration_value),
            'years' => $start->copy()->addYears($this->duration_value),
            default => null,
        };
    }

    /**
     * Get human readable duration
     */
    public function getDurationText(): ?string
    {
        if (!$this->duration_value || !$this->duration_unit) {
            return null;
        }

        $unit = match($this->duration_unit) {
            'days' => $this->duration_value == 1 ? 'dia' : 'dias',
            'months' => $this->duration_value == 1 ? 'mês' : 'meses',
            'years' => $this->duration_value == 1 ? 'ano' : 'anos',
            default => '',
        };

        return "{$this->duration_value} {$unit}";
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if coupon has started
        if ($this->valid_from && now()->isBefore($this->valid_from)) {
            return false;
        }

        // Check if coupon has expired
        if ($this->valid_until && now()->isAfter($this->valid_until)) {
            return false;
        }

        // Check if max uses reached
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            return $amount * ($this->value / 100);
        }

        return min($this->value, $amount);
    }

    /**
     * Increment uses count
     */
    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid coupons
     */
    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('uses_count < max_uses');
            });
    }

    /**
     * Scope for exit intent coupons
     */
    public function scopeExitIntent($query)
    {
        return $query->where('is_exit_intent', true);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
