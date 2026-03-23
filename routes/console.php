<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire-stores')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('sitemap:generate')
    ->daily()
    ->onOneServer();
