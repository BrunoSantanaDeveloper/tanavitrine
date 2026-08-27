<?php

declare(strict_types=1);

use App\Models\Team;

test('a store name made only of special characters receives a usable slug', function (): void {
    $store = Team::factory()->create([
        'name' => '👗 ✨',
        'personal_team' => false,
    ]);

    expect($store->slug)->toBe('loja');
    expect(route('dashboard.stores.edit', $store->slug))
        ->toEndWith('/dashboard/stores/loja/edit');
});

test('fallback store slugs remain unique', function (): void {
    Team::factory()->create(['name' => '👗']);

    $store = Team::factory()->create(['name' => '✨']);

    expect($store->slug)->toBe('loja-1');
});

test('unsafe slugs are normalized when a store is saved', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja Especial',
        'slug' => 'Loja # Especial',
    ]);

    expect($store->slug)->toBe('loja-especial');
});

test('the repair migration restores stores with empty slugs', function (): void {
    $store = Team::factory()->create(['name' => 'Loja com problema']);
    $store->newQuery()->whereKey($store->id)->update(['slug' => '']);

    $migration = require database_path('migrations/2026_08_27_010000_repair_empty_team_slugs.php');
    $migration->up();

    expect($store->fresh()->slug)->toBe('loja-recuperada-'.$store->id);
});
