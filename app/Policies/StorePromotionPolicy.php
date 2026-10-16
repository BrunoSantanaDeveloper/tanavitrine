<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StorePromotion;
use App\Models\User;

final class StorePromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_superadmin === true;
    }

    public function view(User $user, StorePromotion $promotion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StorePromotion $promotion): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, StorePromotion $promotion): bool
    {
        return false;
    }
}
