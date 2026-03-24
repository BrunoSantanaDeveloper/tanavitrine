<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Carbon\CarbonInterface;

final class GrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Crescimento nos Últimos 6 Meses';

    protected static ?int $sort = 7;

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
        '2xl' => 1,
    ];

    public ?string $filter = 'stores';

    protected function getData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $monthLabels = $months->map(function (CarbonInterface $date) {
            return $date->format('M/Y');
        })->toArray();

        $data = match ($this->filter) {
            'users' => $this->getUsersData($months),
            default => $this->getStoresData($months),
        };

        return [
            'datasets' => [
                [
                    'label' => match ($this->filter) {
                        'users' => 'Novos Usuários',
                        default => 'Novas Lojas',
                    },
                    'data' => $data,
                    'borderColor' => match ($this->filter) {
                        'users' => 'rgb(59, 130, 246)',
                        default => 'rgb(251, 191, 36)',
                    },
                    'backgroundColor' => match ($this->filter) {
                        'users' => 'rgba(59, 130, 246, 0.1)',
                        default => 'rgba(251, 191, 36, 0.1)',
                    },
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'stores' => 'Lojas',
            'users' => 'Usuários',
        ];
    }

    protected function getStoresData($months): array
    {
        return $months->map(function (CarbonInterface $date) {
            return (int) Team::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();
    }

    protected function getUsersData($months): array
    {
        return $months->map(function (CarbonInterface $date) {
            return (int) User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();
    }

}
