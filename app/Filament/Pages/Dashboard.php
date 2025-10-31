<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel Administrativo';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\GrowthChartWidget::class,
            \App\Filament\Widgets\StoreTypeDistributionWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 2,
            'sm' => 2,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }
}
