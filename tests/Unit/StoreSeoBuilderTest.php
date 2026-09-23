<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Models\Team;
use App\Models\Category;
use App\Support\StoreSeoBuilder;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'seo.site_name' => 'Tá na Vitrine',
        'seo.site_url' => 'https://tanavitrine.com.br',
        'seo.indexing_enabled' => true,
    ]);
});

it('builds automatic wholesale metadata from store data', function (): void {
    $store = new Team([
        'name' => 'Letrevinho',
        'slug' => 'letrevinho',
        'sale_type' => 'atacado',
        'store_type' => 'ambos',
        'subcategory' => ['Feminino'],
        'is_manufacturer' => true,
        'min_order' => '10 peças',
        'city' => 'Goiânia',
    ]);
    $store->setRelation('category', new Category(['name' => 'Roupas']));

    $seo = app(StoreSeoBuilder::class)->build($store, '/storage/lojas/letrevinho.jpg');

    expect($seo)
        ->title->toBe('Letrevinho: Moda Feminina no Atacado em Goiânia | Tá na Vitrine')
        ->description->toContain('fornecedor de moda feminina no atacado em Goiânia')
        ->description->toContain('Fabricação própria.')
        ->description->toContain('Pedido mínimo de 10 peças.')
        ->canonical->toBe('https://tanavitrine.com.br/loja/letrevinho')
        ->ogImage->toBe('https://tanavitrine.com.br/storage/lojas/letrevinho.jpg');
});

it('omits location and minimum order for virtual retail stores', function (): void {
    $store = new Team([
        'name' => 'Byalance',
        'slug' => 'bya-lance',
        'sale_type' => 'varejo',
        'store_type' => 'virtual',
        'subcategory' => ['Feminino'],
        'min_order' => '10',
        'city' => 'Goiânia',
    ]);
    $store->setRelation('category', new Category(['name' => 'Roupas']));

    $seo = app(StoreSeoBuilder::class)->build($store, '/images/og.png');

    expect($seo['title'])->toBe('Byalance: Moda Feminina no Varejo | Tá na Vitrine')
        ->and($seo['description'])->not->toContain('Goiânia')
        ->and($seo['description'])->not->toContain('Pedido mínimo');
});

it('drops lower priority title blocks without cutting words', function (): void {
    $store = new Team([
        'name' => 'Uma Loja Com Um Nome Comercial Bastante Longo',
        'slug' => 'loja-longa',
        'sale_type' => 'ambos',
        'store_type' => 'ambos',
        'subcategory' => ['Moda Praia e Roupas para Todos os Momentos'],
        'city' => 'Aparecida de Goiânia',
    ]);
    $store->setRelation('category', new Category(['name' => 'Roupas']));

    $seo = app(StoreSeoBuilder::class)->build($store, '/images/og.png');

    expect($seo['title'])->not->toContain('...')
        ->and(mb_strlen($seo['title']))->toBeLessThanOrEqual(70);
});

it('uses a grammatical fallback when category data is missing', function (): void {
    $store = new Team([
        'name' => 'Loja Sem Categoria',
        'slug' => 'loja-sem-categoria',
        'sale_type' => 'atacado',
        'store_type' => 'ambos',
        'city' => 'Goiânia',
    ]);

    $seo = app(StoreSeoBuilder::class)->build($store, '/images/og.png');

    expect($seo['title'])->toBe('Loja Sem Categoria: Atacado em Goiânia | Tá na Vitrine');
});
