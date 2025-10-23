<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLimit extends Model
{
    protected $fillable = [
        'plan_id',
        'module',
        'resource',
        'limit_type',
        'limit_value',
        'period',
        'is_hard_limit',
        'grace_period_days',
        'notify_on_limit',
        'notification_threshold',
        'metadata'
    ];

    protected $casts = [
        'limit_value' => 'integer',
        'is_hard_limit' => 'boolean',
        'notify_on_limit' => 'boolean',
        'notification_threshold' => 'integer',
        'grace_period_days' => 'integer',
        'metadata' => 'array'
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
