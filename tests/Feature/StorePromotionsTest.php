<?php

declare(strict_types=1);

use App\Filament\Resources\StorePromotionResource\Pages\CreateStorePromotion;
use App\Filament\Resources\StorePromotionResource\Pages\EditStorePromotion;
use App\Filament\Resources\StorePromotionResource\Pages\ListStorePromotions;
use App\Models\Media;
use App\Models\Setting;
use App\Models\StorePromotion;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(Carbon::parse('2026-10-15 15:00:00', 'UTC'));
    Setting::setValueByKey('subscription_hide_store_when_expired', false, 'subscriptions', 'boolean');
});

function promotionStore(array $attributes = []): Team
{
    return Team::factory()->create(array_merge([
        'personal_team' => false,
        'status' => 'ativo',
        'slug' => fake()->unique()->slug(),
        'sale_type' => 'atacado',
        'store_type' => 'virtual',
    ], $attributes));
}

function reservePromotion(Team $store, array $attributes = []): StorePromotion
{
    return StorePromotion::create(array_merge([
        'team_id' => $store->id,
        'position' => 1,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'is_active' => true,
    ], $attributes));
}

it('shows only current public promoted stores in their reserved positions', function (): void {
    $second = promotionStore();
    $first = promotionStore();
    reservePromotion($second, ['position' => 2]);
    reservePromotion($first);
    reservePromotion(promotionStore(), ['position' => 3, 'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(14)]);
    reservePromotion(promotionStore(), ['position' => 3, 'starts_at' => now()->subDays(7), 'ends_at' => now()]);
    reservePromotion(promotionStore(), ['position' => 3, 'is_active' => false]);
    $hidden = promotionStore();
    reservePromotion($hidden, ['position' => 3]);
    $hidden->update(['status' => 'suspenso']);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('weeklyFeaturedStores', 2)
        ->where('weeklyFeaturedStores.0.id', $first->id)
        ->where('weeklyFeaturedStores.1.id', $second->id));
});

it('does not substitute normal plan highlights when there are no campaigns', function (): void {
    promotionStore(['featured' => true]);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('weeklyFeaturedStores', 0)
        ->has('featuredStores', 1));
});

it('returns to an empty weekly section when no reservation is eligible', function (string $state): void {
    $store = promotionStore(['featured' => true]);
    $attributes = match ($state) {
        'scheduled' => ['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(8)],
        'ended' => ['starts_at' => now()->subDays(7), 'ends_at' => now()],
        'cancelled' => ['is_active' => false],
        default => [],
    };
    reservePromotion($store, $attributes);
    if ($state === 'unavailable') {
        $store->update(['status' => 'suspenso']);
    }

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('weeklyFeaturedStores', 0)
        ->has('featuredStores', $state === 'unavailable' ? 0 : 1));
})->with(['scheduled', 'ended', 'cancelled', 'unavailable']);

it('ends a campaign at its exact end time', function (): void {
    $promotion = reservePromotion(promotionStore());
    expect(StorePromotion::current()->count())->toBe(1);
    $this->travelTo($promotion->ends_at);
    expect(StorePromotion::current()->count())->toBe(0)
        ->and($promotion->status)->toBe('Encerrado');
});

it('keeps the active storefront photos in the promoted carousel', function (): void {
    $store = promotionStore();
    foreach ([['primary.jpg', true, true], ['second.jpg', true, false], ['hidden.jpg', false, false]] as [$path, $active, $primary]) {
        $photo = Media::create([
            'team_id' => $store->id, 'name' => $path, 'path' => $path,
            'type' => 'image', 'size' => 1, 'is_active' => $active,
        ]);
        $store->photos()->attach($photo, ['order' => $primary ? 0 : 1, 'is_primary' => $primary]);
    }
    reservePromotion($store);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('weeklyFeaturedStores.0.images', 2)
        ->where('weeklyFeaturedStores.0.images.0', \Illuminate\Support\Facades\Storage::url('primary.jpg'))
        ->where('weeklyFeaturedStores.0.images.1', \Illuminate\Support\Facades\Storage::url('second.jpg')));
});

it('allows back to back reservations and reuses cancelled positions', function (): void {
    $first = reservePromotion(promotionStore());
    $next = reservePromotion(promotionStore(), ['starts_at' => $first->ends_at, 'ends_at' => $first->ends_at->copy()->addDays(7)]);
    $first->update(['is_active' => false]);
    $replacement = reservePromotion(promotionStore());

    expect($next->status)->toBe('Agendado')
        ->and($first->status)->toBe('Cancelado')
        ->and($replacement->status)->toBe('Em exibição');
});

it('lets an administrator create and edit a campaign using Brasilia time', function (): void {
    $admin = User::factory()->create(['is_superadmin' => true]);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $store = promotionStore(['featured' => true, 'featured_until' => null]);

    Livewire::test(CreateStorePromotion::class)
        ->fillForm([
            'team_id' => $store->id,
            'position' => 1,
            'starts_at' => '2026-10-16 10:00:00',
            'ends_at' => '2026-10-23 10:00:00',
            'is_active' => true,
            'notes' => 'Pagamento confirmado manualmente',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $promotion = StorePromotion::firstOrFail();
    expect($promotion->starts_at->utc()->format('Y-m-d H:i:s'))->toBe('2026-10-16 13:00:00')
        ->and($promotion->created_by)->toBe($admin->id)
        ->and($store->fresh()->featured)->toBeTrue()
        ->and($store->fresh()->featured_until)->toBeNull();

    Livewire::test(EditStorePromotion::class, ['record' => $promotion->id])
        ->fillForm(['position' => 2])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($promotion->fresh()->position)->toBe(2);
});

it('shows a form error when the position is already reserved', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    reservePromotion(promotionStore());

    Livewire::test(CreateStorePromotion::class)
        ->fillForm([
            'team_id' => promotionStore()->id,
            'position' => 1,
            'starts_at' => '2026-10-16 10:00:00',
            'ends_at' => '2026-10-23 10:00:00',
            'is_active' => true,
        ])->call('create')->assertHasFormErrors(['position']);

    expect(StorePromotion::count())->toBe(1);
});

it('rejects an edit that would occupy another reserved position', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    reservePromotion(promotionStore());
    $other = reservePromotion(promotionStore(), ['position' => 2]);

    Livewire::test(EditStorePromotion::class, ['record' => $other->id])
        ->fillForm(['position' => 1])->call('save')->assertHasFormErrors(['position']);

    expect($other->fresh()->position)->toBe(2);
});

it('cancels a campaign from the admin and preserves its history', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $promotion = reservePromotion(promotionStore());

    Livewire::test(ListStorePromotions::class)->callTableAction('cancel', $promotion);

    expect($promotion->fresh()->is_active)->toBeFalse()
        ->and(StorePromotion::count())->toBe(1)
        ->and(StorePromotion::current()->count())->toBe(0);
});

it('prevents promoting the same store twice during overlapping periods', function (): void {
    $store = promotionStore();
    reservePromotion($store);
    expect(fn () => reservePromotion($store, ['position' => 2]))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('rejects invalid periods and unavailable stores', function (): void {
    $store = promotionStore();
    expect(fn () => reservePromotion($store, ['ends_at' => now()]))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    $store->update(['status' => 'inativo']);
    expect(fn () => reservePromotion($store))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('restricts campaign administration to super administrators', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => false]))->get('/admin/destaques-semanais')->assertForbidden();
    $admin = User::factory()->create(['is_superadmin' => true]);
    $this->actingAs($admin)->get('/admin/destaques-semanais')->assertOk();
    $this->get('/admin/destaques-semanais/create')->assertOk();
});
