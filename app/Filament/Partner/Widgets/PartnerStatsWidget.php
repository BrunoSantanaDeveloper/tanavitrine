<?php

declare(strict_types=1);

namespace App\Filament\Partner\Widgets;

use App\Models\Coupon;
use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class PartnerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $partnerId = Auth::id();

        if (!$partnerId) {
            return [];
        }

        $storesQuery = Team::query()
            ->whereHas('owner.subscriptions', function ($query) use ($partnerId): void {
                $query->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                    $couponQuery->where('partner_id', $partnerId);
                });
            });

        $totalStores = (clone $storesQuery)->distinct('teams.id')->count('teams.id');

        $activeStores = (clone $storesQuery)
            ->where('status', 'ativo')
            ->whereHas('owner.subscriptions', function ($query) use ($partnerId): void {
                $query
                    ->where('stripe_status', 'active')
                    ->whereNull('ends_at')
                    ->whereHas('coupon', function ($couponQuery) use ($partnerId): void {
                        $couponQuery->where('partner_id', $partnerId);
                    });
            })
            ->distinct('teams.id')
            ->count('teams.id');

        $partnerCoupons = Coupon::query()
            ->where('partner_id', $partnerId)
            ->count();

        $activePartnerCoupons = Coupon::query()
            ->where('partner_id', $partnerId)
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Lojas com seu cupom', (string) $totalStores)
                ->description('Total de vitrines que usaram cupons vinculados a você')
                ->color('primary'),

            Stat::make('Lojas ativas', (string) $activeStores)
                ->description('Lojas ativas com assinatura ativa')
                ->color('success'),

            Stat::make('Cupons ativos', "{$activePartnerCoupons} / {$partnerCoupons}")
                ->description('Cupons ativos vinculados ao seu perfil')
                ->color('warning'),
        ];
    }
}

