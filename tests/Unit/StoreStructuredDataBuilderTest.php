<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Models\Team;
use App\Support\StoreStructuredDataBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    config(['seo.site_url' => 'https://tanavitrine.com.br']);
});

it('publishes a connected graph without physical location for virtual stores', function (): void {
    $store = new Team([
        'name' => 'Loja Virtual',
        'store_type' => 'virtual',
        'address' => 'Rua cadastrada anteriormente',
        'city' => 'Goiânia',
        'state' => 'GO',
        'latitude' => '-16.68690000',
        'longitude' => '-49.26480000',
        'google_maps_url' => 'https://maps.google.com/?q=-16.6869,-49.2648',
    ]);
    $store->setAttribute('id', 42);

    $graph = app(StoreStructuredDataBuilder::class)->build(
        store: $store,
        canonical: 'https://tanavitrine.com.br/loja/virtual',
        title: 'Loja Virtual | Tá na Vitrine',
        description: 'Loja de moda no varejo.',
        image: 'https://tanavitrine.com.br/images/loja.jpg',
        logo: 'https://tanavitrine.com.br/images/logo.jpg',
        sameAs: ['https://instagram.com/lojavirtual'],
        breadcrumbs: [
            ['name' => 'Início', 'url' => 'https://tanavitrine.com.br'],
            ['name' => 'Varejo', 'url' => 'https://tanavitrine.com.br/varejo'],
            ['name' => 'Loja Virtual', 'url' => 'https://tanavitrine.com.br/loja/virtual'],
        ],
    );

    $webPage = $graph[0];
    $breadcrumb = $graph[1];
    $storeSchema = $graph[2];

    expect($webPage['@type'])->toBe('WebPage')
        ->and($webPage['mainEntity']['@id'])->toBe('https://tanavitrine.com.br/loja/virtual#store')
        ->and($breadcrumb['@type'])->toBe('BreadcrumbList')
        ->and($breadcrumb['itemListElement'])->toHaveCount(3)
        ->and($storeSchema['@type'])->toBe('OnlineStore')
        ->and($storeSchema['identifier'])->toBe('TV0042')
        ->and($storeSchema['logo'])->toBe('https://tanavitrine.com.br/images/logo.jpg')
        ->and($storeSchema['sameAs'])->toBe(['https://instagram.com/lojavirtual'])
        ->and($storeSchema)->not->toHaveKeys(['address', 'geo', 'hasMap']);
});

it('publishes physical location only for stores with a complete address', function (): void {
    $store = new Team([
        'name' => 'Loja Física',
        'store_type' => 'ambos',
        'address' => 'Rua da Moda',
        'address_number' => '10',
        'city' => 'Goiânia',
        'state' => 'GO',
        'zip_code' => '74000-000',
        'latitude' => '-16.68690000',
        'longitude' => '-49.26480000',
        'google_maps_url' => 'https://maps.google.com/?q=-16.6869,-49.2648',
    ]);

    $graph = app(StoreStructuredDataBuilder::class)->build(
        store: $store,
        canonical: 'https://tanavitrine.com.br/loja/fisica',
        title: 'Loja Física | Tá na Vitrine',
        description: 'Loja de moda no atacado.',
        image: 'https://tanavitrine.com.br/images/og.png',
    );

    $storeSchema = $graph[1];

    expect($storeSchema['@type'])->toBe('Store')
        ->and($storeSchema['address']['@type'])->toBe('PostalAddress')
        ->and($storeSchema['address']['streetAddress'])->toBe('Rua da Moda, 10')
        ->and($storeSchema['geo'])->toMatchArray([
            '@type' => 'GeoCoordinates',
            'latitude' => -16.6869,
            'longitude' => -49.2648,
        ])
        ->and($storeSchema['hasMap'])->toBe('https://maps.google.com/?q=-16.6869,-49.2648');
});

it('uses Organization when the physical store address is incomplete', function (): void {
    $store = new Team([
        'name' => 'Loja sem endereço completo',
        'store_type' => 'fisica',
        'city' => 'Goiânia',
        'state' => 'GO',
    ]);

    $graph = app(StoreStructuredDataBuilder::class)->build(
        store: $store,
        canonical: 'https://tanavitrine.com.br/loja/incompleta',
        title: 'Loja sem endereço completo | Tá na Vitrine',
        description: 'Loja de moda.',
        image: 'https://tanavitrine.com.br/images/og.png',
    );

    expect($graph[1]['@type'])->toBe('Organization')
        ->and($graph[1])->not->toHaveKeys(['address', 'geo', 'hasMap']);
});
