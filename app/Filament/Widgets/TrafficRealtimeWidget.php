<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\TrafficEvent;
use App\Models\TrafficPresence;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

final class TrafficRealtimeWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeSince = now()->subMinutes(2);

        $activePresence = TrafficPresence::query()
            ->where('last_seen_at', '>=', $activeSince);

        $activeVisitors = (int) (clone $activePresence)
            ->distinct()
            ->count('visitor_id');

        $activeIps = (int) (clone $activePresence)
            ->whereNotNull('ip_hash')
            ->distinct()
            ->count('ip_hash');

        $deviceCounts = (clone $activePresence)
            ->selectRaw('device_type, COUNT(DISTINCT visitor_id) as total')
            ->groupBy('device_type')
            ->pluck('total', 'device_type');

        $mobileNow = (int) ($deviceCounts['mobile'] ?? 0);
        $desktopNow = (int) ($deviceCounts['desktop'] ?? 0);
        $tabletNow = (int) ($deviceCounts['tablet'] ?? 0);

        $now = now();
        $viewsLast15 = (int) TrafficEvent::query()
            ->whereBetween('occurred_at', [$now->copy()->subMinutes(15), $now])
            ->count();

        $viewsPrevious15 = (int) TrafficEvent::query()
            ->whereBetween('occurred_at', [$now->copy()->subMinutes(30), $now->copy()->subMinutes(15)])
            ->count();

        $viewsTrend = $viewsPrevious15 > 0
            ? round((($viewsLast15 - $viewsPrevious15) / $viewsPrevious15) * 100, 1)
            : ($viewsLast15 > 0 ? 100.0 : 0.0);

        $uniqueVisitors24h = (int) TrafficEvent::query()
            ->where('occurred_at', '>=', now()->subDay())
            ->distinct()
            ->count('visitor_id');

        return [
            Stat::make('Visitantes Ativos Agora', Number::format($activeVisitors))
                ->description("Mobile: {$mobileNow} · Desktop: {$desktopNow} · Tablet: {$tabletNow}")
                ->descriptionIcon('heroicon-m-signal')
                ->color('info'),

            Stat::make('IPs Únicos Ativos', Number::format($activeIps))
                ->description('Janela de atividade: últimos 2 minutos')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

            Stat::make('Visualizações (15 min)', Number::format($viewsLast15))
                ->description(($viewsTrend >= 0 ? '+' : '') . "{$viewsTrend}% vs 15 min anteriores")
                ->descriptionIcon($viewsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($viewsTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Visitantes Únicos (24h)', Number::format($uniqueVisitors24h))
                ->description('Base de visitantes distintos no último dia')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
