<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Superadmins can view all teams in admin panel
        if ($user->is_superadmin) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        // Superadmins can view any team
        if ($user->is_superadmin) {
            return true;
        }

        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Superadmins can create teams
        if ($user->is_superadmin) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        // Superadmins can edit any team
        if ($user->is_superadmin) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can add team members.
     */
    public function addTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update team member permissions.
     */
    public function updateTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can remove team members.
     */
    public function removeTeamMember(User $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        // Superadmins can delete any team
        if ($user->is_superadmin) {
            return true;
        }

        return $user->ownsTeam($team);
    }
}
