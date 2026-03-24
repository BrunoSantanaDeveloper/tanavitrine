<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TeamView;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\QueryException;
use Illuminate\Support\Number;

final class ViewsPeriodWidget extends ChartWidget
{
    protected static ?string $heading = 'Visualizações por Período';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'week';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $summary = null;

    protected function getData(): array
    {
        $this->summary = $this->buildSummary($this->filter ?? 'week');

        return [
            'datasets' => [
                [
                    'label' => $this->summary['dataset_label'],
                    'data' => $this->summary['data'],
                    'borderColor' => 'rgb(8, 145, 178)',
                    'backgroundColor' => 'rgba(8, 145, 178, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $this->summary['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
        $summary = $this->summary ?? $this->buildSummary($this->filter ?? 'week');
        $formattedChange = ($summary['change_percent'] > 0 ? '+' : '')
            . number_format($summary['change_percent'], 1, ',', '.');

        return sprintf(
            'Período: %s | Anterior: %s (%s%%)',
            Number::format($summary['total']),
            Number::format($summary['previous_total']),
            $formattedChange,
        );
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, total: int, previous_total: int, change_percent: float}
     */
    private function buildSummary(string $filter): array
    {
        $now = CarbonImmutable::now();

        return match ($filter) {
            'today' => $this->buildTodaySummary(),
            'last30' => $this->buildDaySummary($now->subDays(29)->startOfDay(), $now, 'Últimos 30 dias'),
            'month' => $this->buildDaySummary($now->startOfMonth(), $now, 'Mês atual'),
            default => $this->buildDaySummary($now->subDays(6)->startOfDay(), $now, 'Últimos 7 dias'),
        };
    }

    /**
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, total: int, previous_total: int, change_percent: float}
     */
    private function buildTodaySummary(): array
    {
        $start = CarbonImmutable::now()->startOfDay();
        $end = CarbonImmutable::now();

        $hourlyTotals = array_fill(0, 24, 0);

        try {
            $events = TeamView::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->get(['viewed_at']);

            foreach ($events as $event) {
                $hour = (int) $event->viewed_at->format('G');
                $hourlyTotals[$hour]++;
            }
        } catch (QueryException $exception) {
            report($exception);
        }

        $labels = [];
        $data = [];

        foreach (range(0, 23) as $hour) {
            $labels[] = sprintf('%02dh', $hour);
            $data[] = $hourlyTotals[$hour];
        }

        [$previousStart, $previousEnd] = $this->previousRange($start, $end);
        $previousTotal = $this->countViewsInRange($previousStart, $previousEnd);

        return $this->formatSummary('Hoje (por hora)', $labels, $data, $previousTotal);
    }

    /**
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, total: int, previous_total: int, change_percent: float}
     */
    private function buildDaySummary(CarbonImmutable $start, CarbonImmutable $end, string $label): array
    {
        $dailyMap = $this->dailyViewsMap($start, $end);
        $labels = [];
        $data = [];

        for ($cursor = $start->startOfDay(); $cursor->lessThanOrEqualTo($end->startOfDay()); $cursor = $cursor->addDay()) {
            $bucket = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $data[] = $dailyMap[$bucket] ?? 0;
        }

        [$previousStart, $previousEnd] = $this->previousRange($start, $end);
        $previousTotal = $this->countViewsInRange($previousStart, $previousEnd);

        return $this->formatSummary($label, $labels, $data, $previousTotal);
    }

    /**
     * @return array<string, int>
     */
    private function dailyViewsMap(CarbonImmutable $start, CarbonImmutable $end): array
    {
        try {
            return TeamView::query()
                ->selectRaw('DATE(viewed_at) as bucket_date, COUNT(*) as total')
                ->whereBetween('viewed_at', [$start, $end])
                ->groupByRaw('DATE(viewed_at)')
                ->orderBy('bucket_date')
                ->pluck('total', 'bucket_date')
                ->map(fn ($value): int => (int) $value)
                ->all();
        } catch (QueryException $exception) {
            report($exception);

            return [];
        }
    }

    private function countViewsInRange(CarbonImmutable $start, CarbonImmutable $end): int
    {
        try {
            return (int) TeamView::query()
                ->whereBetween('viewed_at', [$start, $end])
                ->count();
        } catch (QueryException $exception) {
            report($exception);

            return 0;
        }
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function previousRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $durationInSeconds = $start->diffInSeconds($end) + 1;
        $previousEnd = $start->subSecond();
        $previousStart = $previousEnd->subSeconds($durationInSeconds - 1);

        return [$previousStart, $previousEnd];
    }

    /**
     * @param array<int, string> $labels
     * @param array<int, int> $data
     *
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, total: int, previous_total: int, change_percent: float}
     */
    private function formatSummary(string $datasetLabel, array $labels, array $data, int $previousTotal): array
    {
        $total = array_sum($data);

        $changePercent = $previousTotal > 0
            ? round((($total - $previousTotal) / $previousTotal) * 100, 1)
            : ($total > 0 ? 100.0 : 0.0);

        return [
            'dataset_label' => $datasetLabel,
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'previous_total' => $previousTotal,
            'change_percent' => $changePercent,
        ];
    }
}
