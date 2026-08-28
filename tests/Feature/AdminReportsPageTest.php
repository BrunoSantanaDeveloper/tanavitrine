<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Models\TeamView;
use App\Filament\Pages\Reports;
use App\Models\StoreInteraction;

test('super administrator can open the store reports page', function (): void {
    $admin = User::factory()->create(['is_superadmin' => true]);

    Team::factory()->create([
        'name' => 'Loja com métricas',
        'slug' => 'loja-com-metricas',
        'personal_team' => false,
        'views_count' => 10,
        'whatsapp_clicks' => 4,
    ]);

    $this->actingAs($admin)
        ->get('/admin/relatorios')
        ->assertOk()
        ->assertSee('Relatórios de lojas')
        ->assertSee('Loja com métricas');
});

test('reports combine dated views and clicks for a selected period', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja por período',
        'slug' => 'loja-por-periodo',
        'personal_team' => false,
    ]);

    TeamView::query()->create([
        'team_id' => $store->id,
        'viewed_at' => '2026-08-10 10:00:00',
    ]);
    TeamView::query()->create([
        'team_id' => $store->id,
        'viewed_at' => '2026-07-10 10:00:00',
    ]);
    StoreInteraction::query()->create([
        'team_id' => $store->id,
        'type' => StoreInteraction::TYPE_WHATSAPP,
        'occurred_at' => '2026-08-12 10:00:00',
    ]);
    StoreInteraction::query()->create([
        'team_id' => $store->id,
        'type' => StoreInteraction::TYPE_WHATSAPP,
        'occurred_at' => '2026-07-12 10:00:00',
    ]);

    $page = new Reports();
    $page->startDate = '2026-08-01';
    $page->endDate = '2026-08-31';

    $method = new ReflectionMethod($page, 'reportQuery');
    $result = $method->invoke($page)->whereKey($store->id)->firstOrFail();

    expect((int) $result->report_views)->toBe(1)
        ->and((int) $result->report_whatsapp)->toBe(1)
        ->and((int) $result->report_website)->toBe(0);
});

test('reports load only the configured number of stores per page', function (): void {
    $admin = User::factory()->create(['is_superadmin' => true]);

    foreach (range(1, 30) as $position) {
        Team::factory()->create([
            'name' => sprintf('Ranking-%03d-Item', $position),
            'slug' => sprintf('ranking-%03d-item', $position),
            'personal_team' => false,
            'views_count' => 31 - $position,
        ]);
    }

    $this->actingAs($admin)
        ->get('/admin/relatorios')
        ->assertOk()
        ->assertSee('Ranking-001-Item')
        ->assertSee('Ranking-025-Item')
        ->assertDontSee('Ranking-026-Item')
        ->assertSee('fi-pagination-next-btn', false);
});
