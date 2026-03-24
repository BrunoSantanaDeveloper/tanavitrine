<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TrafficEvent;
use App\Models\TrafficPresence;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Number;

final class TrafficDeviceDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Dispositivos';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 1;

    public ?string $filter = 'week';

    /**
     * @var array<string, int>|null
     */
    private ?array $counts = null;

    protected function getData(): array
    {
        [$start, $end] = $this->rangeFromFilter($this->filter ?? 'week');

        $grouped = TrafficEvent::query()
            ->selectRaw('device_type, COUNT(*) as total')
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('device_type')
            ->pluck('total', 'device_type');

        $desktop = (int) ($grouped['desktop'] ?? 0);
        $mobile = (int) ($grouped['mobile'] ?? 0);
        $tablet = (int) ($grouped['tablet'] ?? 0);
        $other = (int) ($grouped['unknown'] ?? 0);

        $this->counts = [
            'desktop' => $desktop,
            'mobile' => $mobile,
            'tablet' => $tablet,
            'other' => $other,
        ];

        $total = $desktop + $mobile + $tablet + $other;

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
                'labels' => ['Sem dados no período'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Eventos',
                    'data' => [$desktop, $mobile, $tablet, $other],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(251, 191, 36, 0.85)',
                        'rgba(107, 114, 128, 0.75)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(251, 191, 36)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['Desktop', 'Mobile', 'Tablet', 'Outros'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoje',
            'week' => 'Últimos 7 dias',
            'last30' => 'Últimos 30 dias',
            'month' => 'Mês atual',
        ];
    }

    public function getDescription(): ?string
    {
        if (!$this->counts) {
            $this->getData();
        }

        $activeNow = (int) TrafficPresence::query()
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->distinct()
            ->count('visitor_id');

        $total = array_sum($this->counts ?? []);

        return sprintf(
            'Eventos no período: %s | Ativos agora: %s',
            Number::format($total),
            Number::format($activeNow),
        );
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

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function rangeFromFilter(string $filter): array
    {
        $now = CarbonImmutable::now();

        return match ($filter) {
            'today' => [$now->startOfDay(), $now],
            'last30' => [$now->subDays(29)->startOfDay(), $now],
            'month' => [$now->startOfMonth(), $now],
            default => [$now->subDays(6)->startOfDay(), $now],
        };
    }
}
