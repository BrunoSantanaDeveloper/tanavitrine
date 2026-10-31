<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Team;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

function recommendationStore(array $attributes = []): Team
{
    return Team::factory()->create(array_merge([
        'slug' => fake()->unique()->slug(),
        'personal_team' => false,
        'status' => 'ativo',
        'sale_type' => 'ambos',
        'store_type' => 'virtual',
    ], $attributes));
}

it('prioritizes matching subcategories and fills remaining suggestions from the same category', function (): void {
    $category = Category::create(['name' => 'Moda', 'slug' => 'moda', 'is_active' => true]);
    $current = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans', 'Vestidos']]);
    $first = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans'], 'created_at' => now()->subDays(3)]);
    $second = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Vestidos'], 'created_at' => now()->subDays(2)]);
    $otherFallback = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Esportivo'], 'created_at' => now()->subDay()]);
    $fallback = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Infantil']]);
    recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans'], 'status' => 'suspenso']);
    recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans'], 'personal_team' => true]);
    recommendationStore(['subcategory' => ['Jeans']]);

    $this->get("/loja/{$current->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('StoreDetail')
        ->where('store.id', $current->id)
        ->has('relatedStores', 3)
        ->where('relatedStores', function ($stores) use ($first, $second): bool {
            return collect($stores)->take(2)->pluck('id')->sort()->values()->all()
                === collect([$first->id, $second->id])->sort()->values()->all();
        })
        ->where('relatedStores.2.id', fn ($id): bool => in_array($id, [$fallback->id, $otherFallback->id], true))
        ->where('relatedStores.2.subcategory', null)
        ->where('relatedStores.2.category', 'Moda')
        ->where('relatedStores.2.saleType', 'Atacado e varejo'));
});

it('limits recommendations to three distinct storefronts and never includes the current store', function (): void {
    $category = Category::create(['name' => 'Acessórios', 'slug' => 'acessorios', 'is_active' => true]);
    $current = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Óculos']]);
    $candidates = collect(range(1, 4))->map(fn () => recommendationStore([
        'category_id' => $category->id, 'subcategory' => ['Óculos'],
    ]));

    $this->get("/loja/{$current->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('relatedStores', 3)
        ->where('relatedStores', function ($stores) use ($candidates, $current): bool {
            $ids = collect($stores)->pluck('id');

            return $ids->unique()->count() === 3
                && ! $ids->contains($current->id)
                && $ids->diff($candidates->pluck('id'))->isEmpty();
        }));
});

it('returns no suggestions when there are no relevant public stores', function (): void {
    $current = recommendationStore();
    recommendationStore(['subcategory' => ['Jeans']]);

    $this->get("/loja/{$current->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('relatedStores', 0));

    $current->update(['subcategory' => ['Vestidos']]);
    $this->get("/loja/{$current->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('relatedStores', 0));
});

it('uses the primary active storefront photo and ignores hidden photos and logos', function (): void {
    $category = Category::create(['name' => 'Roupas', 'slug' => 'roupas', 'is_active' => true]);
    $current = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans']]);
    $suggestion = recommendationStore(['category_id' => $category->id, 'subcategory' => ['Jeans']]);

    foreach ([
        ['other.jpg', true, null, false],
        ['primary.jpg', true, null, true],
        ['hidden.jpg', false, null, true],
        ['logo.jpg', true, 'logo', true],
    ] as $order => [$path, $active, $photoCategory, $primary]) {
        $photo = Media::create([
            'team_id' => $suggestion->id, 'name' => $path, 'path' => $path,
            'type' => 'image', 'size' => 1, 'is_active' => $active, 'category' => $photoCategory,
        ]);
        $suggestion->photos()->attach($photo, ['order' => $order, 'is_primary' => $primary]);
    }

    $this->get("/loja/{$current->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('relatedStores', 1)
        ->where('relatedStores.0.id', $suggestion->id)
        ->where('relatedStores.0.image', \Illuminate\Support\Facades\Storage::url('primary.jpg')));
});
