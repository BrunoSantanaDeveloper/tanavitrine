<?php

declare(strict_types=1);

use App\Filament\Pages\Reports;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\StoreInteraction;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

test('opening clicks accumulate independently of views and preserve store modification date', function (): void {
    $store = Team::factory()->create([
        'slug' => 'loja-cliques',
        'personal_team' => false,
        'status' => 'ativo',
        'plan_id' => null,
    ]);
    $updatedAt = $store->updated_at->toISOString();
    $this->travel(10)->minutes();

    $this->post('/loja/loja-cliques/track/open')->assertOk();
    $this->post('/loja/loja-cliques/track/open')->assertOk();

    $store->refresh();
    expect($store->store_open_clicks)->toBe(2)
        ->and($store->views_count)->toBe(0)
        ->and($store->updated_at->toISOString())->toBe($updatedAt)
        ->and($store->interactionEvents()->where('type', StoreInteraction::TYPE_STORE_OPEN)->count())->toBe(2);

    $this->get('/loja/loja-cliques')->assertOk();
    $this->get('/loja/loja-cliques')->assertOk();
    expect($store->fresh()->store_open_clicks)->toBe(2);
});

test('unavailable stores cannot receive opening clicks', function (bool $personal, string $status): void {
    $store = Team::factory()->create([
        'slug' => 'loja-indisponivel',
        'personal_team' => $personal,
        'status' => $status,
    ]);

    $this->post('/loja/loja-indisponivel/track/open')->assertNotFound();
    expect($store->fresh()->store_open_clicks)->toBe(0);
})->with([[true, 'ativo'], [false, 'pendente']]);

test('admin reports expose both accumulated and period opening clicks', function (): void {
    $store = Team::factory()->create(['personal_team' => false]);
    $this->travelTo(now()->startOfDay()->subDays(2));
    $store->incrementStoreOpenClicks();
    $this->travelBack();
    $store->incrementStoreOpenClicks();

    $page = new Reports();
    $query = new ReflectionMethod($page, 'reportQuery');
    expect((int) $query->invoke($page)->whereKey($store->id)->firstOrFail()->report_store_open)->toBe(2);

    $page->startDate = now()->toDateString();
    $page->endDate = now()->toDateString();
    expect((int) $query->invoke($page)->whereKey($store->id)->firstOrFail()->report_store_open)->toBe(1);
});

test('owners see opening clicks in their stores and analytics but cannot access another owners analytics', function (): void {
    $owner = User::factory()->create(['is_superadmin' => false]);
    $plan = Plan::query()->create([
        'name' => 'Plano Analytics',
        'currency' => 'brl',
        'features' => ['analytics' => ['views']],
    ]);
    $store = Team::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'loja-painel-cliques',
        'personal_team' => false,
        'plan_id' => $plan->id,
    ]);
    $store->incrementStoreOpenClicks();

    $this->actingAs($owner)->get('/dashboard/stores')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/MyStores')->where('stores.0.store_open_clicks', 1));
    $this->get('/dashboard/stores/loja-painel-cliques/analytics')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/StoreAnalytics')->where('store.store_open_clicks', 1));
    $this->actingAs(User::factory()->create(['is_superadmin' => false]))->get('/dashboard/stores/loja-painel-cliques/analytics')
        ->assertNotFound();
});
