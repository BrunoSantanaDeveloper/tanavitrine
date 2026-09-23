<?php

declare(strict_types=1);

use App\Models\Team;
use App\Support\StoreStructuredDataBuilder;

it('uses OnlineStore without a physical address for virtual stores', function (): void {
    $store = new Team([
        'name' => 'Loja Virtual',
        'store_type' => 'virtual',
        'city' => 'Goiânia',
        'state' => 'GO',
    ]);

    $schema = app(StoreStructuredDataBuilder::class)->build(
        $store,
        'https://tanavitrine.com.br/loja/virtual',
        'Loja de moda no varejo.',
        'https://tanavitrine.com.br/images/og.png',
        ['https://instagram.com/lojavirtual'],
    );

    expect($schema['@type'])->toBe('OnlineStore')
        ->and($schema)->not->toHaveKey('address')
        ->and($schema['sameAs'])->toBe(['https://instagram.com/lojavirtual']);
});

it('uses Store with PostalAddress only for a complete physical address', function (): void {
    $store = new Team([
        'name' => 'Loja Física',
        'store_type' => 'ambos',
        'address' => 'Rua da Moda',
        'address_number' => '10',
        'city' => 'Goiânia',
        'state' => 'GO',
        'zip_code' => '74000-000',
    ]);

    $schema = app(StoreStructuredDataBuilder::class)->build(
        $store,
        'https://tanavitrine.com.br/loja/fisica',
        'Loja de moda no atacado.',
        'https://tanavitrine.com.br/images/og.png',
    );

    expect($schema['@type'])->toBe('Store')
        ->and($schema['address']['@type'])->toBe('PostalAddress')
        ->and($schema['address']['streetAddress'])->toBe('Rua da Moda, 10');
});
