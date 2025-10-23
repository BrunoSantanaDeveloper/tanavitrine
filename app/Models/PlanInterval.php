<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanInterval extends Model
{
    protected $table = 'plan_intervals';

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function interval()
    {
        return $this->belongsTo(Interval::class);
    }
}