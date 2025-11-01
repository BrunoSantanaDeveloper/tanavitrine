<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTeamSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:sync-subscriptions {--force : Force sync even if subscription exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subscriptions for all teams that have a plan assigned';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting team subscriptions synchronization...');

        $teams = Team::with(['owner', 'plan'])
            ->whereNotNull('plan_id')
            ->whereHas('owner')
            ->get();

        if ($teams->isEmpty()) {
            $this->info('No teams found with assigned plans.');
            return self::SUCCESS;
        }

        $this->info("Found {$teams->count()} teams with plans assigned.");

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($teams->count());
        $progressBar->start();

        foreach ($teams as $team) {
            try {
                $user = $team->owner;
                $subscription = $user->subscription('default');

                // Skip if subscription exists and --force not used
                if ($subscription && !$this->option('force')) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Get plan and interval
                $plan = $team->plan;
                $planInterval = $plan->intervals()->first();

                if (!$planInterval) {
                    $this->warn("\nTeam '{$team->name}' plan has no intervals configured. Skipping.");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $stripePriceId = $planInterval->pivot->stripe_price_id ?? 'price_' . $planInterval->pivot->id;
                $price = (float) ($planInterval->pivot->price ?? 0);

                if ($subscription) {
                    // Update existing subscription
                    $subscription->update([
                        'name' => 'default',
                        'type' => $plan->name,
                        'stripe_price' => $stripePriceId,
                        'stripe_status' => 'active',
                        'original_price' => $price,
                        'final_price' => $price,
                    ]);
                } else {
                    // Create new subscription
                    $user->subscriptions()->create([
                        'name' => 'default',
                        'type' => $plan->name,
                        'stripe_id' => 'sub_admin_' . uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $stripePriceId,
                        'quantity' => 1,
                        'trial_ends_at' => null,
                        'ends_at' => null,
                        'original_price' => $price,
                        'final_price' => $price,
                    ]);
                }

                $synced++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError syncing team '{$team->name}': " . $e->getMessage());
                Log::error('Team subscription sync failed', [
                    'team_id' => $team->id,
                    'error' => $e->getMessage(),
                ]);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Synchronization completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Synced', $synced],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        return self::SUCCESS;
    }
}
