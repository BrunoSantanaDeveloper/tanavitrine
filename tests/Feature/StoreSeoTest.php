<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\Setting;
use App\Models\Category;

beforeEach(function (): void {
    config([
        'seo.site_name' => 'Tá na Vitrine',
        'seo.site_url' => 'https://tanavitrine.com.br',
        'seo.indexing_enabled' => true,
    ]);

    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

it('renders automatic store metadata and safe structured data in the initial html', function (): void {
    $category = Category::create([
        'name' => 'Roupas',
        'slug' => 'roupas-seo',
        'is_active' => true,
    ]);
    $store = Team::factory()->create([
        'name' => 'Letrevinho',
        'slug' => 'letrevinho-seo',
        'personal_team' => false,
        'status' => 'ativo',
        'category_id' => $category->id,
        'subcategory' => ['Feminino'],
        'sale_type' => 'atacado',
        'store_type' => 'ambos',
        'city' => 'Goiânia',
        'state' => 'GO',
        'is_manufacturer' => true,
        'min_order' => '10',
        'instagram' => '@letrevinho',
    ]);

    $response = $this->get("/loja/{$store->slug}");

    $response
        ->assertOk()
        ->assertSee('<title>Letrevinho: Moda Feminina no Atacado em Goiânia | Tá na Vitrine</title>', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('https://instagram.com/letrevinho', false)
        ->assertSee('Fabricantes', false)
        ->assertSee('Pedido mínimo de 10 peças.', false)
        ->assertDontSee('R$', false);

    expect(mb_substr_count($response->getContent(), '<title>'))->toBe(1)
        ->and($response->getContent())->not->toContain('Tá na Vitrine | Tá na Vitrine');
});

it('does not change content updated_at when a store page is viewed', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja Métrica',
        'slug' => 'loja-metrica-seo',
        'personal_team' => false,
        'status' => 'ativo',
        'sale_type' => 'varejo',
        'store_type' => 'virtual',
    ]);
    $updatedAt = $store->updated_at?->toISOString();

    $this->travel(10)->minutes();
    $this->get("/loja/{$store->slug}")->assertOk();

    $store->refresh();
    expect($store->views_count)->toBe(1)
        ->and($store->updated_at?->toISOString())->toBe($updatedAt);
});

it('emits valid json ld without allowing script tag breakout', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja </script><script>alert(1)</script>',
        'slug' => 'loja-json-seguro',
        'personal_team' => false,
        'status' => 'ativo',
        'sale_type' => 'varejo',
        'store_type' => 'virtual',
    ]);

    $content = $this->get("/loja/{$store->slug}")
        ->assertOk()
        ->getContent();

    expect(preg_match(
        '#<script type="application/ld\+json">\s*(.*?)\s*</script>#s',
        $content,
        $matches,
    ))->toBe(1);

    $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

    $storeSchema = collect($schema['@graph'])->first(
        static fn (array $item): bool => str_ends_with((string) ($item['@id'] ?? ''), '#store')
    );

    expect($schema)->toBeArray()
        ->and($matches[1])->not->toContain('</script>')
        ->and($storeSchema['name'])->toBe('Loja alert(1)');
});

it('connects webpage breadcrumbs and physical store data in json ld', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja Física SEO',
        'slug' => 'loja-fisica-estrutura-seo',
        'personal_team' => false,
        'status' => 'ativo',
        'sale_type' => 'atacado',
        'store_type' => 'fisica',
        'address' => 'Rua da Moda',
        'address_number' => '123',
        'city' => 'Goiânia',
        'state' => 'GO',
        'zip_code' => '74000-000',
        'latitude' => '-16.68690000',
        'longitude' => '-49.26480000',
        'google_maps_url' => 'https://maps.google.com/?q=-16.6869,-49.2648',
    ]);

    $content = $this->get("/loja/{$store->slug}")
        ->assertOk()
        ->assertSee('Atacado', false)
        ->getContent();

    preg_match(
        '#<script type="application/ld\+json">\s*(.*?)\s*</script>#s',
        $content,
        $matches,
    );
    $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    $webPage = collect($schema['@graph'])->firstWhere('@type', 'WebPage');
    $breadcrumb = collect($schema['@graph'])->firstWhere('@type', 'BreadcrumbList');
    $storeSchema = collect($schema['@graph'])->firstWhere('@type', 'Store');

    expect($webPage['mainEntity']['@id'])->toBe($storeSchema['@id'])
        ->and($webPage['breadcrumb']['@id'])->toBe($breadcrumb['@id'])
        ->and(array_column($breadcrumb['itemListElement'], 'name'))->toBe([
            'Início',
            'Atacado',
            'Loja Física SEO',
        ])
        ->and($storeSchema['identifier'])->toBe('TV'.mb_str_pad((string) $store->id, 4, '0', STR_PAD_LEFT))
        ->and($storeSchema['address']['streetAddress'])->toBe('Rua da Moda, 123')
        ->and($storeSchema['geo']['latitude'])->toBe(-16.6869)
        ->and($storeSchema['hasMap'])->toBe('https://maps.google.com/?q=-16.6869,-49.2648');
});

it('does not change content updated_at when interactions are tracked', function (): void {
    $store = Team::factory()->create([
        'name' => 'Loja Interação SEO',
        'slug' => 'loja-interacao-seo',
        'personal_team' => false,
    ]);
    $updatedAt = $store->updated_at?->toISOString();

    $this->travel(10)->minutes();
    $this->post("/loja/{$store->slug}/track/whatsapp")->assertOk();

    $store->refresh();
    expect($store->whatsapp_clicks)->toBe(1)
        ->and($store->updated_at?->toISOString())->toBe($updatedAt);
});
