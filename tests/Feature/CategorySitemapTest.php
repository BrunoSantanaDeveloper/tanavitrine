<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\Setting;
use App\Models\Category;

beforeEach(function (): void {
    $this->sitemapPath = public_path('sitemap.xml');
    $this->originalSitemap = file_exists($this->sitemapPath)
        ? file_get_contents($this->sitemapPath)
        : null;

    config([
        'seo.site_url' => 'https://tanavitrine.com.br',
        'sitemap.base_url' => 'https://tanavitrine.com.br',
        'seo.category_pages_enabled' => true,
        'seo.category_indexing_enabled' => true,
        'seo.category_min_stores' => 3,
    ]);

    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

afterEach(function (): void {
    if (is_string($this->originalSitemap)) {
        file_put_contents($this->sitemapPath, $this->originalSitemap);

        return;
    }

    if (file_exists($this->sitemapPath)) {
        unlink($this->sitemapPath);
    }
});

it('adds only eligible parent categories when both rollout gates are enabled', function (): void {
    $eligible = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);
    $thin = Category::create(['name' => 'Joias', 'slug' => 'joias', 'is_active' => true]);
    $child = Category::create([
        'name' => 'Moda Feminina',
        'slug' => 'moda-feminina',
        'parent_id' => $eligible->id,
        'is_active' => true,
    ]);

    foreach (range(1, 3) as $number) {
        Team::factory()->create([
            'name' => "Loja {$number}",
            'slug' => "loja-sitemap-{$number}",
            'personal_team' => false,
            'status' => 'ativo',
            'category_id' => $eligible->id,
        ]);
    }

    Team::factory()->create([
        'name' => 'Loja Joias',
        'slug' => 'loja-joias-sitemap',
        'personal_team' => false,
        'status' => 'ativo',
        'category_id' => $thin->id,
    ]);
    Team::factory()->create([
        'name' => 'Loja Subcategoria',
        'slug' => 'loja-subcategoria-sitemap',
        'personal_team' => false,
        'status' => 'ativo',
        'category_id' => $child->id,
    ]);

    $this->artisan('sitemap:generate')->assertSuccessful();

    $sitemap = file_get_contents($this->sitemapPath);

    expect($sitemap)
        ->toContain('https://tanavitrine.com.br/categorias')
        ->toContain('https://tanavitrine.com.br/categoria/roupas')
        ->not->toContain('https://tanavitrine.com.br/categoria/joias')
        ->not->toContain('https://tanavitrine.com.br/categoria/moda-feminina');
});

it('omits all category URLs while category indexing is disabled', function (): void {
    config(['seo.category_indexing_enabled' => false]);
    $category = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);

    foreach (range(1, 3) as $number) {
        Team::factory()->create([
            'name' => "Loja {$number}",
            'slug' => "loja-bloqueada-{$number}",
            'personal_team' => false,
            'status' => 'ativo',
            'category_id' => $category->id,
        ]);
    }

    $this->artisan('sitemap:generate')->assertSuccessful();

    expect(file_get_contents($this->sitemapPath))
        ->not->toContain('https://tanavitrine.com.br/categorias')
        ->not->toContain('https://tanavitrine.com.br/categoria/roupas');
});
