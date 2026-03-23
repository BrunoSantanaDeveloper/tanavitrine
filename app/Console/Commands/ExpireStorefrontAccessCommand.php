<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\SubscriptionAccessRuleService;

final class ExpireStorefrontAccessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire-stores {--dry-run : Apenas simula, sem salvar alterações}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca vitrines como inativas quando o acesso da assinatura expira';

    public function handle(SubscriptionAccessRuleService $rules): int
    {
        if (!$rules->shouldHideStoreWhenExpired()) {
            $this->info('Regra "tirar vitrine do ar ao expirar" está desativada. Nada a fazer.');
            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $candidates = Team::query()
            ->where('personal_team', false)
            ->where('status', 'ativo')
            ->with([
                'owner',
                'owner.subscriptions' => fn ($query) => $query
                    ->where('name', 'default')
                    ->orderByDesc('created_at'),
            ])
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nenhuma vitrine ativa encontrada.');
            return self::SUCCESS;
        }

        $expiredCount = 0;
        $updatedCount = 0;

        foreach ($candidates as $store) {
            $subscription = $store->owner?->subscriptions?->first();
            $hasAccess = $rules->hasActiveAccess($subscription);

            if ($hasAccess) {
                continue;
            }

            $expiredCount++;

            if (!$isDryRun) {
                $store->update(['status' => 'inativo']);
                $updatedCount++;
            }

            Log::info('Storefront expired and marked for deactivation', [
                'store_id' => $store->id,
                'store_slug' => $store->slug,
                'user_id' => $store->user_id,
                'subscription_id' => $subscription?->id,
                'trial_ends_at' => $subscription?->trial_ends_at,
                'discount_ends_at' => $subscription?->discount_ends_at,
                'ends_at' => $subscription?->ends_at,
                'dry_run' => $isDryRun,
            ]);
        }

        $this->info("Vitrines expiradas encontradas: {$expiredCount}");
        $this->info($isDryRun
            ? 'Dry-run ativo: nenhuma vitrine foi alterada.'
            : "Vitrines marcadas como inativas: {$updatedCount}");
        $this->info('Acesso ao painel permanece disponível para os usuários.');

        return self::SUCCESS;
    }
}

