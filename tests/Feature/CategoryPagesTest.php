<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\Setting;
use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config([
        'seo.site_name' => 'Tá na Vitrine',
        'seo.site_url' => 'https://tanavitrine.com.br',
        'seo.indexing_enabled' => true,
        'seo.category_pages_enabled' => true,
        'seo.category_indexing_enabled' => true,
        'seo.category_min_stores' => 3,
    ]);

    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

function createCategoryPageStore(Category $category, array $attributes = []): Team
{
    return Team::factory()->create(array_merge([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
        'personal_team' => false,
        'status' => 'ativo',
        'category_id' => $category->id,
        'sale_type' => 'atacado',
        'store_type' => 'virtual',
        'whatsapp' => '62999999999',
    ], $attributes));
}

it('keeps category routes behind the publication gate', function (): void {
    config(['seo.category_pages_enabled' => false]);

    $this->get('/categorias')
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
});

it('lists only parent categories that contain public stores', function (): void {
    $published = Category::create([
        'name' => 'Roupas',
        'slug' => 'roupas',
        'description' => 'Moda para todos os estilos.',
        'is_active' => true,
    ]);
    $empty = Category::create(['name' => 'Calçados', 'slug' => 'calcados', 'is_active' => true]);
    $child = Category::create([
        'name' => 'Moda Feminina',
        'slug' => 'moda-feminina',
        'parent_id' => $published->id,
        'is_active' => true,
    ]);

    createCategoryPageStore($published);
    createCategoryPageStore($published);
    createCategoryPageStore($published);
    createCategoryPageStore($empty, ['status' => 'inativo']);
    createCategoryPageStore($child);

    $this->get('/categorias')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Categories')
            ->has('categories', 1)
            ->where('categories.0.name', 'Roupas')
            ->where('categories.0.stores_count', 3)
            ->where('seo.robots', 'index, follow'));
});

it('renders stores from the requested parent category without prices', function (): void {
    $clothing = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);
    $shoes = Category::create(['name' => 'Calçados', 'slug' => 'calcados', 'is_active' => true]);

    $store = createCategoryPageStore($clothing, [
        'name' => 'Vitrine da Moda',
        'slug' => 'vitrine-da-moda',
        'min_order' => '10',
        'featured' => true,
    ]);
    createCategoryPageStore($clothing);
    createCategoryPageStore($clothing);
    createCategoryPageStore($shoes, ['name' => 'Loja de Calçados']);

    $response = $this->get('/categoria/roupas');

    $response
        ->assertOk()
        ->assertSee('<title>Fornecedores de Roupas no Atacado e Varejo | Tá na Vitrine</title>', false)
        ->assertSee('"@type":"CollectionPage"', false)
        ->assertSee('"@type":"ItemList"', false)
        ->assertDontSee('"@type":"Product"', false)
        ->assertDontSee('R$', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('CategoryStores')
            ->has('stores.data', 3)
            ->where('category.stores_count', 3)
            ->where('seo.robots', 'index, follow')
            ->where('seo.canonical', 'https://tanavitrine.com.br/categoria/roupas')
            ->where('stores.data.0.url', route('store.show', $store->slug))
            ->where('stores.data.0.minOrder', '10 peças'));
});

it('keeps the pilot pages noindex while the indexing gate is disabled', function (): void {
    config(['seo.category_indexing_enabled' => false]);
    $category = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);
    createCategoryPageStore($category);

    $this->get('/categoria/roupas')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('keeps thin category pages out of the index', function (): void {
    $category = Category::create(['name' => 'Joias', 'slug' => 'joias', 'is_active' => true]);
    createCategoryPageStore($category);

    $this->get('/categoria/joias')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.robots', 'noindex, nofollow'));
});

it('does not publish child or empty category pages in the first rollout', function (): void {
    $parent = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);
    $child = Category::create([
        'name' => 'Moda Feminina',
        'slug' => 'moda-feminina',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);
    $empty = Category::create(['name' => 'Calçados', 'slug' => 'calcados', 'is_active' => true]);
    createCategoryPageStore($child);

    $this->get('/categoria/moda-feminina')->assertNotFound();
    $this->get("/categoria/{$empty->slug}")->assertNotFound();
});

it('uses self canonical URLs and continuous item positions on page two', function (): void {
    $category = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);

    foreach (range(1, 13) as $number) {
        createCategoryPageStore($category, [
            'name' => "Loja {$number}",
            'slug' => "loja-{$number}",
            'created_at' => now()->subMinutes($number),
        ]);
    }

    $this->get('/categoria/roupas?page=2')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://tanavitrine.com.br/categoria/roupas?page=2">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->has('stores.data', 1)
            ->where('stores.current_page', 2)
            ->where('seo.canonical', 'https://tanavitrine.com.br/categoria/roupas?page=2')
            ->where('structuredData.2.itemListElement.0.position', 13));
});
