<?php

declare(strict_types=1);

beforeEach(function (): void {
    config([
        'seo.site_name' => 'Tá na Vitrine',
        'seo.site_url' => 'https://tanavitrine.com.br',
        'seo.indexing_enabled' => true,
        'seo.category_pages_enabled' => false,
    ]);
});

it('renders public metadata in the initial html', function (): void {
    $response = $this->get('/atacado');

    $response
        ->assertOk()
        ->assertSee('<title>Atacado | Tá na Vitrine</title>', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://tanavitrine.com.br/atacado">', false)
        ->assertSee('<meta property="og:title" content="Atacado | Tá na Vitrine">', false)
        ->assertDontSee('%s', false)
        ->assertDontSee('Tá na Vitrine | Tá na Vitrine', false);

    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();
    expect(mb_substr_count($response->getContent(), '<title>'))->toBe(1);
});

it('renders legal-page metadata on the server', function (): void {
    $response = $this->get('/privacy-policy');

    $response
        ->assertOk()
        ->assertSee('<title>Política de Privacidade | Tá na Vitrine</title>', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://tanavitrine.com.br/privacy-policy">', false);

    expect($response->headers->has('X-Robots-Tag'))->toBeFalse();
});

it('keeps authentication pages out of the index', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<title>Login | Tá na Vitrine</title>', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('blocks indexing globally when the environment gate is disabled', function (): void {
    config(['seo.indexing_enabled' => false]);

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('returns a controlled noindex 404 for unfinished category pages', function (): void {
    $this->get('/categorias')
        ->assertNotFound()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
});
