<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-stores')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('sitemap:generate')
    ->daily()
    ->onOneServer();

Schedule::command('traffic:cleanup')
    ->dailyAt('03:20')
    ->withoutOverlapping();
