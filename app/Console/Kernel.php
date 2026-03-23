<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CheckDigitalSignagePermissions::class,
        Commands\ExpireStorefrontAccessCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('subscriptions:expire-stores')
            ->hourly()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
