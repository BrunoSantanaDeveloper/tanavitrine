<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

final class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Total de lojas
        $totalStores = (int) Team::count();
        $storesThisMonth = (int) Team::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $storesLastMonth = (int) Team::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $storesGrowth = $storesLastMonth > 0
            ? round((($storesThisMonth - $storesLastMonth) / $storesLastMonth) * 100, 1)
            : 100;

        // Total de usuários
        $totalUsers = (int) User::count();
        $usersThisMonth = (int) User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersLastMonth = (int) User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $usersGrowth = $usersLastMonth > 0
            ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : 100;

        // Lojas ativas vs inativas
        $activeStores = (int) Team::where('status', 'ativo')->count();
        $activePercentage = $totalStores > 0 ? round(($activeStores / $totalStores) * 100, 1) : 0;

        // Total de visualizações
        $totalViews = (int) Team::sum('views_count');
        $viewsThisMonth = (int) Team::whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('views_count');

        // Lojas em destaque
        $featuredStores = (int) Team::where('featured', true)
            ->where(function($query) {
                $query->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            })
            ->count();

        return [
            Stat::make('Total de Lojas', Number::format($totalStores))
                ->description($storesGrowth >= 0 ? "+{$storesGrowth}% em relação ao mês anterior" : "{$storesGrowth}% em relação ao mês anterior")
                ->descriptionIcon($storesGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([$storesLastMonth, $storesThisMonth])
                ->color($storesGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Lojas Ativas', Number::format($activeStores))
                ->description("{$activePercentage}% do total")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Usuários Cadastrados', Number::format($totalUsers))
                ->description($usersGrowth >= 0 ? "+{$usersGrowth}% em relação ao mês anterior" : "{$usersGrowth}% em relação ao mês anterior")
                ->descriptionIcon($usersGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([$usersLastMonth, $usersThisMonth])
                ->color($usersGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Total de Visualizações', Number::format($totalViews))
                ->description("Este mês: " . Number::format($viewsThisMonth))
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Lojas em Destaque', Number::format($featuredStores))
                ->description($totalStores > 0 ? round(($featuredStores / $totalStores) * 100, 1) . "% do total" : "0%")
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
