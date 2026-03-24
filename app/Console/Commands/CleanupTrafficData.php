<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TrafficEvent;
use App\Models\TrafficPresence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class CleanupTrafficData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traffic:cleanup
        {--events-days=90 : Retenção de eventos em dias}
        {--presence-minutes=120 : Janela máxima de presença em minutos}
        {--dry-run : Exibe contagens sem apagar dados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old traffic_events and stale traffic_presence records.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!Schema::hasTable('traffic_events') || !Schema::hasTable('traffic_presence')) {
            $this->warn('Traffic tables not found. Skipping cleanup.');
            return self::SUCCESS;
        }

        $eventsDays = max(1, (int) $this->option('events-days'));
        $presenceMinutes = max(1, (int) $this->option('presence-minutes'));
        $dryRun = (bool) $this->option('dry-run');

        $eventsBefore = now()->subDays($eventsDays);
        $presenceBefore = now()->subMinutes($presenceMinutes);

        $eventsQuery = TrafficEvent::query()
            ->where('occurred_at', '<', $eventsBefore);

        $presenceQuery = TrafficPresence::query()
            ->where('last_seen_at', '<', $presenceBefore);

        $eventsCount = (clone $eventsQuery)->count();
        $presenceCount = (clone $presenceQuery)->count();

        $this->info("Traffic cleanup window: events>{$eventsDays}d, presence>{$presenceMinutes}min");
        $this->line("Candidates: events={$eventsCount}, presence={$presenceCount}");

        if ($dryRun) {
            $this->comment('Dry-run enabled. No records were deleted.');
            return self::SUCCESS;
        }

        $deletedEvents = $eventsCount > 0 ? $eventsQuery->delete() : 0;
        $deletedPresence = $presenceCount > 0 ? $presenceQuery->delete() : 0;

        $this->info("Cleanup done. Deleted: events={$deletedEvents}, presence={$deletedPresence}");

        return self::SUCCESS;
    }
}
