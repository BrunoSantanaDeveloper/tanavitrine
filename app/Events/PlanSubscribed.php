<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Plan;
use App\Models\Team;

class PlanSubscribed
{
    public function __construct(
        public Plan $plan,
        public Team $team
    ) {}
}
