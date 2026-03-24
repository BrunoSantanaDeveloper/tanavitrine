<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TrafficEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Number;

final class TrafficTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Tendência de Tráfego';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 1;

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
                    'borderColor' => 'rgb(14, 165, 233)',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.16)',
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
        $change = ($summary['change_percent'] > 0 ? '+' : '')
            . number_format($summary['change_percent'], 1, ',', '.');

        return sprintf(
            'Views: %s | Únicos: %s | IPs: %s | vs anterior: %s%%',
            Number::format($summary['views']),
            Number::format($summary['unique_visitors']),
            Number::format($summary['unique_ips']),
            $change,
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
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, views: int, unique_visitors: int, unique_ips: int, previous_views: int, change_percent: float}
     */
    private function buildSummary(string $filter): array
    {
        $now = CarbonImmutable::now();

        return match ($filter) {
            'today' => $this->buildHourlySummary($now->startOfDay(), $now, 'Hoje (por hora)'),
            'last30' => $this->buildDailySummary($now->subDays(29)->startOfDay(), $now, 'Últimos 30 dias'),
            'month' => $this->buildDailySummary($now->startOfMonth(), $now, 'Mês atual'),
            default => $this->buildDailySummary($now->subDays(6)->startOfDay(), $now, 'Últimos 7 dias'),
        };
    }

    /**
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, views: int, unique_visitors: int, unique_ips: int, previous_views: int, change_percent: float}
     */
    private function buildHourlySummary(CarbonImmutable $start, CarbonImmutable $end, string $label): array
    {
        $hourlyTotals = array_fill(0, 24, 0);

        try {
            $hourExpression = $this->hourBucketExpression();
            $rows = TrafficEvent::query()
                ->selectRaw("{$hourExpression} as bucket_hour, COUNT(*) as total")
                ->whereBetween('occurred_at', [$start, $end])
                ->groupByRaw($hourExpression)
                ->pluck('total', 'bucket_hour')
                ->all();

            foreach ($rows as $hour => $total) {
                $normalizedHour = (int) $hour;
                if ($normalizedHour < 0 || $normalizedHour > 23) {
                    continue;
                }

                $hourlyTotals[$normalizedHour] = (int) $total;
            }
        } catch (QueryException $exception) {
            report($exception);

            $events = TrafficEvent::query()
                ->whereBetween('occurred_at', [$start, $end])
                ->get(['occurred_at']);

            foreach ($events as $event) {
                $hour = (int) $event->occurred_at->format('G');
                $hourlyTotals[$hour]++;
            }
        }

        $labels = [];
        $data = [];
        foreach (range(0, 23) as $hour) {
            $labels[] = sprintf('%02dh', $hour);
            $data[] = $hourlyTotals[$hour];
        }

        return $this->finalizeSummary($label, $labels, $data, $start, $end);
    }

    private function hourBucketExpression(): string
    {
        $driver = TrafficEvent::query()->getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => "CAST(strftime('%H', occurred_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(HOUR FROM occurred_at)',
            default => 'HOUR(occurred_at)',
        };
    }

    /**
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, views: int, unique_visitors: int, unique_ips: int, previous_views: int, change_percent: float}
     */
    private function buildDailySummary(CarbonImmutable $start, CarbonImmutable $end, string $label): array
    {
        $dailyMap = TrafficEvent::query()
            ->selectRaw('DATE(occurred_at) as bucket_date, COUNT(*) as total')
            ->whereBetween('occurred_at', [$start, $end])
            ->groupByRaw('DATE(occurred_at)')
            ->orderBy('bucket_date')
            ->pluck('total', 'bucket_date')
            ->map(fn ($value): int => (int) $value)
            ->all();

        $labels = [];
        $data = [];
        for ($cursor = $start->startOfDay(); $cursor->lessThanOrEqualTo($end->startOfDay()); $cursor = $cursor->addDay()) {
            $bucket = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $data[] = $dailyMap[$bucket] ?? 0;
        }

        return $this->finalizeSummary($label, $labels, $data, $start, $end);
    }

    /**
     * @param array<int, string> $labels
     * @param array<int, int> $data
     *
     * @return array{dataset_label: string, labels: array<int, string>, data: array<int, int>, views: int, unique_visitors: int, unique_ips: int, previous_views: int, change_percent: float}
     */
    private function finalizeSummary(string $datasetLabel, array $labels, array $data, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $views = array_sum($data);

        $uniqueVisitors = (int) TrafficEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->distinct()
            ->count('visitor_id');

        $uniqueIps = (int) TrafficEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNotNull('ip_hash')
            ->distinct()
            ->count('ip_hash');

        [$previousStart, $previousEnd] = $this->previousRange($start, $end);
        $previousViews = (int) TrafficEvent::query()
            ->whereBetween('occurred_at', [$previousStart, $previousEnd])
            ->count();

        $changePercent = $previousViews > 0
            ? round((($views - $previousViews) / $previousViews) * 100, 1)
            : ($views > 0 ? 100.0 : 0.0);

        return [
            'dataset_label' => $datasetLabel,
            'labels' => $labels,
            'data' => $data,
            'views' => $views,
            'unique_visitors' => $uniqueVisitors,
            'unique_ips' => $uniqueIps,
            'previous_views' => $previousViews,
            'change_percent' => $changePercent,
        ];
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
}
