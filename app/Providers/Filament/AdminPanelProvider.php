<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\View\PanelsRenderHook;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\HtmlString;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Maartenpaauw\Filament\Cashier\Stripe\BillingProvider;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->font('Outfit')
            ->brandName('TanaVitrine Admin')
            ->favicon(asset('favicon.ico'))
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\Reports::class,
                \App\Filament\Pages\SubscriptionRules::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\ViewsPeriodWidget::class,
                \App\Filament\Widgets\GrowthChartWidget::class,
                \App\Filament\Widgets\StoreTypeDistributionWidget::class,
                \App\Filament\Widgets\TrafficRealtimeWidget::class,
                \App\Filament\Widgets\TrafficTrendWidget::class,
                \App\Filament\Widgets\TrafficDeviceDistributionWidget::class,
                \App\Filament\Widgets\TopTrafficPagesWidget::class,
            ])
            ->middleware([
                'web',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
<style>
  /* In reorder mode, lock primary featured photo row from drag interaction. */
  .fi-ta-row.tv-primary-photo-locked.cursor-move {
    pointer-events: none;
    opacity: .75;
  }

  .fi-ta-row.tv-primary-photo-locked.cursor-move .fi-icon-btn {
    opacity: .45;
  }
</style>
HTML)
            )
            ->tenantBillingProvider(new BillingProvider('default'));
    }
}
