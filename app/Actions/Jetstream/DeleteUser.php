<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionAccessRuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\DeletesUsers;

final readonly class DeleteUser implements DeletesUsers
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        private DeletesTeams $deletesTeams,
        private SubscriptionAccessRuleService $accessRuleService
    ) {}

    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        $deletionGuard = $this->accessRuleService->getAccountDeletionGuard($user);
        if (($deletionGuard['can_delete_account'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'delete_account' => $deletionGuard['message']
                    ?? 'Para excluir sua conta, primeiro cancele o plano ativo.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            $this->deleteTeams($user);
            $user->deleteProfilePhoto();
            $user->tokens->each->delete();
            $user->delete();
        });
    }

    /**
     * Delete the teams and team associations attached to the user.
     */
    private function deleteTeams(User $user): void
    {
        $user->teams()->detach();

        $user->ownedTeams->each(function (Team $team): void {
            $this->deletesTeams->delete($team);
        });
    }
}
