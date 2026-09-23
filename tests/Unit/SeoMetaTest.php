<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Support\SeoMeta;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'seo.site_name' => 'Tá na Vitrine',
        'seo.site_url' => 'https://tanavitrine.com.br',
        'seo.indexing_enabled' => true,
    ]);
});

it('builds final metadata from one canonical source', function (): void {
    $seo = app(SeoMeta::class)->make(
        title: 'Atacado',
        description: '  Encontre   fornecedores de moda. ',
        canonical: '/atacado',
        indexable: true,
    );

    expect($seo)
        ->title->toBe('Atacado | Tá na Vitrine')
        ->description->toBe('Encontre fornecedores de moda.')
        ->robots->toBe('index, follow')
        ->canonical->toBe('https://tanavitrine.com.br/atacado')
        ->ogTitle->toBe($seo['title'])
        ->ogDescription->toBe($seo['description'])
        ->ogUrl->toBe($seo['canonical'])
        ->twitterTitle->toBe($seo['title']);
});

it('does not duplicate the site name', function (): void {
    $seo = app(SeoMeta::class)->make(
        title: 'Atacado | Tá na Vitrine | Tá na Vitrine',
        canonical: '/atacado',
        indexable: true,
    );

    expect($seo['title'])->toBe('Atacado | Tá na Vitrine');
});

it('keeps every page noindex when environment indexing is disabled', function (): void {
    config(['seo.indexing_enabled' => false]);

    $seo = app(SeoMeta::class)->make(
        title: 'Atacado',
        canonical: '/atacado',
        indexable: true,
        overrides: ['robots' => 'index, follow'],
    );

    expect($seo['robots'])->toBe('noindex, nofollow');
});

it('forces canonical URLs onto the configured site origin', function (): void {
    $seo = app(SeoMeta::class)->make(
        title: 'Planos',
        canonical: 'http://localhost:8010/prices?source=test',
        indexable: true,
    );

    expect($seo['canonical'])->toBe('https://tanavitrine.com.br/prices');
});
