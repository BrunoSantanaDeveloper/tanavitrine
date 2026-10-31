<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

function homeSearchStore(array $attributes = []): Team
{
    return Team::factory()->create(array_merge([
        'personal_team' => false,
        'status' => 'ativo',
        'slug' => fake()->unique()->slug(),
        'sale_type' => 'atacado',
        'store_type' => 'virtual',
    ], $attributes));
}

it('shows only eligible highlighted stores in the home catalog while keeping other stores searchable', function (): void {
    $highlighted = homeSearchStore(['featured' => true]);
    homeSearchStore(['featured' => false]);
    homeSearchStore(['featured' => true, 'featured_until' => now()->subDay()]);
    homeSearchStore(['featured' => true, 'status' => 'inativo']);
    homeSearchStore(['featured' => true, 'personal_team' => true]);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('featuredStores', 1)
        ->where('featuredStores.0.id', $highlighted->id)
        ->missing('recentStores'));

    $this->get('/buscar')->assertOk()->assertInertia(fn (Assert $page) => $page->has('stores', 3));
});

it('offers quick searches from public stores ranked by frequency without duplicate labels', function (): void {
    homeSearchStore(['subcategory' => ['Jeans', 'Jeans', 'Moda Praia', ' ']]);
    homeSearchStore(['sale_type' => 'varejo', 'subcategory' => [' jeans ', 'Alfaiataria & Linho']]);
    homeSearchStore(['subcategory' => ['Jeans']]);
    homeSearchStore(['status' => 'inativo', 'subcategory' => ['Oculta']]);
    homeSearchStore(['personal_team' => true, 'subcategory' => ['Pessoal']]);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->where('quickSearchSubcategories', ['Jeans', 'Alfaiataria & Linho', 'Moda Praia']));
});

it('limits quick searches to six subcategories and permits an empty list', function (): void {
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('quickSearchSubcategories', 0));

    homeSearchStore(['subcategory' => ['A', 'B', 'C', 'D', 'E', 'F', 'G']]);
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('quickSearchSubcategories', ['A', 'B', 'C', 'D', 'E', 'F']));
});

it('searches the public catalog across wholesale retail and mixed stores', function (): void {
    $wholesale = homeSearchStore(['sale_type' => 'atacado']);
    $retail = homeSearchStore(['sale_type' => 'varejo']);
    $mixed = homeSearchStore(['sale_type' => 'ambos']);
    homeSearchStore(['status' => 'suspenso']);
    homeSearchStore(['personal_team' => true]);

    $this->get('/buscar')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Varejo')
        ->where('isSearchPage', true)
        ->has('stores', 3)
        ->where('stores', fn ($stores): bool => collect($stores)->pluck('id')->sort()->values()->all() === [$wholesale->id, $retail->id, $mixed->id])
        ->where('initialSearchFilters.busca', '')
        ->where('initialSearchFilters.subcategorias', [])
        ->where('seo.robots', 'noindex, nofollow'));
});

it('initializes search terms and subcategory shortcuts from the query string', function (): void {
    homeSearchStore(['subcategory' => ['Alfaiataria & Linho']]);
    $query = http_build_query(['busca' => 'Moda feminina', 'subcategorias' => ['Alfaiataria & Linho']]);

    $this->get('/buscar?'.$query)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('initialSearchFilters.busca', 'Moda feminina')
        ->where('initialSearchFilters.subcategorias', ['Alfaiataria & Linho'])
        ->where('subcategories.0.name', 'Alfaiataria & Linho'));
});

it('rejects malformed or oversized search input', function (): void {
    $this->getJson('/buscar?'.http_build_query(['busca' => ['invalid']]))
        ->assertUnprocessable()->assertJsonValidationErrors('busca');
    $this->getJson('/buscar?'.http_build_query(['busca' => str_repeat('x', 151)]))
        ->assertUnprocessable()->assertJsonValidationErrors('busca');
    $this->getJson('/buscar?'.http_build_query(['subcategorias' => 'invalid']))
        ->assertUnprocessable()->assertJsonValidationErrors('subcategorias');
});
