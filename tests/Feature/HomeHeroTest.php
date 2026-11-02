<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Filament\Facades\Filament;
use App\Filament\Pages\HomeHero;
use Illuminate\Http\UploadedFile;
use App\Services\HomeHeroSettings;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function heroTestImage(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('hero.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
    ));
}

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('public');
    Process::fake(function ($process) {
        file_put_contents($process->command[array_key_last($process->command)], 'generated-first-frame');

        return Process::result();
    });
});

it('provides the existing hero appearance when no settings have been saved', function (): void {
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.title', 'Encontre fornecedores')
        ->where('hero.background_type', 'color')
        ->where('hero.background_color', '#ffffff')
        ->where('hero.overlay_enabled', true)
        ->where('hero.image_url', null)
        ->where('hero.video_url', null)
        ->where('hero.poster_url', null)
        ->missing('hero.poster_path')
        ->missing('hero.image_path'));
});

it('lets administrators save text and colors and immediately updates the home props', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    // Prime the cache before saving to also check invalidation.
    app(HomeHeroSettings::class)->get();

    Livewire::test(HomeHero::class)
        ->fillForm([
            'title' => 'Conheça nossas lojas',
            'background_color' => '#123456',
            'button_color' => '#abcdef',
            'badge' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(HomeHero::class)->assertFormSet(['title' => 'Conheça nossas lojas', 'background_color' => '#123456']);

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.title', 'Conheça nossas lojas')
        ->where('hero.background_color', '#123456')
        ->where('hero.button_color', '#abcdef')
        ->where('hero.highlight', 'em poucos cliques'));
});

it('lets administrators disable and reenable the overlay while preserving its color and opacity', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    app(HomeHeroSettings::class)->save(['overlay_color' => '#abcdef', 'overlay_opacity' => 65]);

    Livewire::test(HomeHero::class)
        ->fillForm(['overlay_enabled' => false])
        ->assertFormFieldIsDisabled('overlay_color')
        ->assertFormFieldIsDisabled('overlay_opacity')
        ->call('save')->assertHasNoFormErrors();

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.overlay_enabled', false)
        ->where('hero.overlay_color', '#abcdef')
        ->where('hero.overlay_opacity', 65));

    Livewire::test(HomeHero::class)
        ->assertFormSet(['overlay_enabled' => false, 'overlay_color' => '#abcdef', 'overlay_opacity' => 65])
        ->fillForm(['overlay_enabled' => true])
        ->assertFormFieldIsEnabled('overlay_color')
        ->assertFormFieldIsEnabled('overlay_opacity')
        ->call('save')->assertHasNoFormErrors();

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.overlay_enabled', true)
        ->where('hero.overlay_color', '#abcdef')
        ->where('hero.overlay_opacity', 65));
});

it('uploads a background photo and keeps it when switching to a color background', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'image', 'image_path' => heroTestImage()])
        ->call('save')->assertHasNoFormErrors();

    $path = app(HomeHeroSettings::class)->get()['image_path'];
    Storage::disk('public')->assertExists($path);
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.background_type', 'image')
        ->where('hero.image_url', Storage::disk('public')->url($path)));

    Livewire::test(HomeHero::class)->fillForm(['background_type' => 'color'])->call('save')->assertHasNoFormErrors();
    expect(app(HomeHeroSettings::class)->get()['image_path'])->toBe($path);
});

it('accepts a 50 MB video without a photo and preserves it when switching background types', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm([
            'background_type' => 'video',
            'video_path' => UploadedFile::fake()->create('hero.mp4', 51200, 'video/mp4'),
            'overlay_opacity' => 65,
        ])
        ->assertFormFieldIsVisible('video_path')
        ->assertFormFieldIsHidden('image_path')
        ->assertFormFieldExists('video_path', fn ($field): bool => $field->getMaxSize() === 51200)
        ->call('save')->assertHasNoFormErrors();

    $settings = app(HomeHeroSettings::class)->get();
    Storage::disk('public')->assertExists($settings['video_path']);
    Storage::disk('public')->assertExists($settings['poster_path']);
    expect($settings['image_path'])->toBeNull();
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('hero.background_type', 'video')
        ->where('hero.video_url', Storage::disk('public')->url($settings['video_path']))
        ->where('hero.poster_url', Storage::disk('public')->url($settings['poster_path']))
        ->where('hero.overlay_opacity', fn ($value): bool => (int) $value === 65));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'color'])
        ->assertFormFieldIsHidden('video_path')
        ->assertFormFieldIsHidden('image_path')
        ->call('save')->assertHasNoFormErrors();

    expect(app(HomeHeroSettings::class)->get()['video_path'])->toBe($settings['video_path']);
    expect(app(HomeHeroSettings::class)->get()['image_path'])->toBeNull();
    expect(app(HomeHeroSettings::class)->get()['poster_path'])->toBe($settings['poster_path']);
});

it('reuses a generated poster and replaces it when a new video is uploaded', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'video', 'video_path' => UploadedFile::fake()->create('first.mp4', 10, 'video/mp4')])
        ->call('save')->assertHasNoFormErrors();

    $firstPoster = app(HomeHeroSettings::class)->get()['poster_path'];
    Livewire::test(HomeHero::class)->fillForm(['title' => 'Novo título'])->call('save')->assertHasNoFormErrors();
    expect(app(HomeHeroSettings::class)->get()['poster_path'])->toBe($firstPoster);
    Process::assertRanTimes(fn ($process): bool => is_array($process->command), 1);

    Livewire::test(HomeHero::class)
        ->fillForm(['video_path' => null])
        ->fillForm(['video_path' => [UploadedFile::fake()->create('second.mp4', 10, 'video/mp4')]])
        ->call('save')->assertHasNoFormErrors();
    expect(app(HomeHeroSettings::class)->get()['poster_path'])->not->toBe($firstPoster);
    Process::assertRanTimes(fn ($process): bool => is_array($process->command), 2);
});

it('keeps the previous video and poster if first-frame extraction fails', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'video', 'video_path' => UploadedFile::fake()->create('first.mp4', 10, 'video/mp4')])
        ->call('save')->assertHasNoFormErrors();
    $previous = app(HomeHeroSettings::class)->get();
    Process::fake(fn () => Process::result(errorOutput: 'Invalid video', exitCode: 1));

    Livewire::test(HomeHero::class)
        ->fillForm(['video_path' => null])
        ->fillForm(['video_path' => [UploadedFile::fake()->create('invalid.mp4', 10, 'video/mp4')]])
        ->call('save')->assertHasFormErrors(['video_path']);

    expect(app(HomeHeroSettings::class)->get())->toBe($previous);
    expect(Storage::disk('public')->files('hero/posters'))->toHaveCount(1);
});

it('rejects invalid colors opacity modes and missing media without changing saved settings', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'video', 'title_color' => 'red', 'overlay_opacity' => 101])
        ->call('save')
        ->assertHasFormErrors(['title_color', 'overlay_opacity', 'video_path'])
        ->assertHasNoFormErrors(['image_path']);

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'image'])->call('save')->assertHasFormErrors(['image_path']);

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'invalid'])->call('save')->assertHasFormErrors(['background_type']);

    expect(app(HomeHeroSettings::class)->get())->toBe(app(HomeHeroSettings::class)->defaults());
});

it('rejects unsupported or oversized media uploads', function (): void {
    $this->actingAs(User::factory()->create(['is_superadmin' => true]));

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'image', 'image_path' => UploadedFile::fake()->create('invalid.svg', 10, 'image/svg+xml')])
        ->call('save')->assertHasFormErrors(['image_path']);

    Livewire::test(HomeHero::class)
        ->fillForm(['background_type' => 'video', 'video_path' => UploadedFile::fake()->create('large.mp4', 51201, 'video/mp4')])
        ->call('save')->assertHasFormErrors(['video_path']);
});

it('only allows administrators to access the hero editor', function (): void {
    expect(HomeHero::canAccess())->toBeFalse();
    $this->get('/admin/hero-pagina-inicial')->assertRedirect();

    $this->actingAs(User::factory()->create(['is_superadmin' => false]));
    $this->get('/admin/hero-pagina-inicial')->assertForbidden();
    Livewire::test(HomeHero::class)->assertForbidden();

    $this->actingAs(User::factory()->create(['is_superadmin' => true]));
    $this->get('/admin/hero-pagina-inicial')->assertOk();
});
