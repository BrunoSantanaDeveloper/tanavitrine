<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\StoreInteraction;

test('all report interactions are tracked independently of the store plan', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja Relatório',
        'slug' => 'loja-relatorio',
        'personal_team' => false,
        'plan_id' => null,
    ]);

    $routes = [
        'whatsapp' => StoreInteraction::TYPE_WHATSAPP,
        'website' => StoreInteraction::TYPE_WEBSITE,
        'phone' => StoreInteraction::TYPE_PHONE,
        'map' => StoreInteraction::TYPE_MAP,
        'instagram' => StoreInteraction::TYPE_INSTAGRAM,
        'facebook' => StoreInteraction::TYPE_FACEBOOK,
        'tiktok' => StoreInteraction::TYPE_TIKTOK,
    ];

    foreach ($routes as $route => $type) {
        $this->post("/loja/{$store->slug}/track/{$route}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('store_interactions', [
            'team_id' => $store->id,
            'type' => $type,
        ]);
    }

    $store->refresh();

    expect($store->whatsapp_clicks)->toBe(1)
        ->and($store->website_clicks)->toBe(1)
        ->and($store->phone_clicks)->toBe(1)
        ->and($store->map_clicks)->toBe(1)
        ->and($store->instagram_clicks)->toBe(1)
        ->and($store->facebook_clicks)->toBe(1)
        ->and($store->tiktok_clicks)->toBe(1)
        ->and($store->interactionEvents()->count())->toBe(7);
});
