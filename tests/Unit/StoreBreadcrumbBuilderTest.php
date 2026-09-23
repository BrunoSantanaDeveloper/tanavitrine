<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Models\Team;
use App\Support\StoreBreadcrumbBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    config(['seo.site_url' => 'https://tanavitrine.com.br']);
});

it('prioritizes the manufacturers landing page for manufacturers', function (): void {
    $store = new Team([
        'name' => 'Fábrica da Moda',
        'sale_type' => 'varejo',
        'is_manufacturer' => true,
    ]);

    $breadcrumbs = app(StoreBreadcrumbBuilder::class)->build(
        $store,
        'https://tanavitrine.com.br/loja/fabrica-da-moda',
    );

    expect(array_column($breadcrumbs, 'name'))->toBe(['Início', 'Fabricantes', 'Fábrica da Moda'])
        ->and($breadcrumbs[1]['url'])->toBe('https://tanavitrine.com.br/fabricantes')
        ->and($breadcrumbs[2]['current'])->toBeTrue();
});

it('uses the wholesale landing page for wholesale and mixed stores', function (string $saleType): void {
    $store = new Team([
        'name' => 'Loja Atacadista',
        'sale_type' => $saleType,
    ]);

    $breadcrumbs = app(StoreBreadcrumbBuilder::class)->build(
        $store,
        'https://tanavitrine.com.br/loja/atacadista',
    );

    expect($breadcrumbs[1]['name'])->toBe('Atacado')
        ->and($breadcrumbs[1]['url'])->toBe('https://tanavitrine.com.br/atacado');
})->with(['atacado', 'ambos']);

it('uses the retail landing page for retail stores', function (): void {
    $store = new Team([
        'name' => 'Loja Varejista',
        'sale_type' => 'varejo',
    ]);

    $breadcrumbs = app(StoreBreadcrumbBuilder::class)->build(
        $store,
        'https://tanavitrine.com.br/loja/varejista',
    );

    expect($breadcrumbs[1]['name'])->toBe('Varejo')
        ->and($breadcrumbs[1]['url'])->toBe('https://tanavitrine.com.br/varejo');
});
