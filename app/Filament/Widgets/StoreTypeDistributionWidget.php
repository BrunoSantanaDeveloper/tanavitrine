<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Widgets\ChartWidget;

final class StoreTypeDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribuição por Tipo de Venda';

    protected static ?int $sort = 8;

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
        '2xl' => 1,
    ];

    protected function getData(): array
    {
        $atacado = (int) Team::where('sale_type', 'atacado')->count();
        $varejo = (int) Team::where('sale_type', 'varejo')->count();
        $ambos = (int) Team::where('sale_type', 'ambos')->count();
        $total = $atacado + $varejo + $ambos;

        if ($total === 0) {
            return [
                'datasets' => [
                    [
                        'label' => 'Sem dados',
                        'data' => [1],
                        'backgroundColor' => ['rgba(107, 114, 128, 0.6)'],
                        'borderColor' => ['rgb(107, 114, 128)'],
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['Sem dados'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Lojas',
                    'data' => [$atacado, $varejo, $ambos],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',   // Azul para Atacado
                        'rgba(16, 185, 129, 0.8)',   // Verde para Varejo
                        'rgba(251, 191, 36, 0.8)',   // Amarelo para Ambos
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(251, 191, 36)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Atacado', 'Varejo', 'Ambos'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 2,
            'cutout' => '62%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => false,
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
