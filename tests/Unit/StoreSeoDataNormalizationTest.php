<?php

declare(strict_types=1);

use App\Support\StoreSocialUrl;
use App\Support\StoreMinimumOrder;

it('normalizes legacy minimum orders as quantities of pieces', function (): void {
    expect(StoreMinimumOrder::pieces('50 peças'))->toBe(50)
        ->and(StoreMinimumOrder::pieces('20 peças para atacado'))->toBe(20)
        ->and(StoreMinimumOrder::pieces('Sem pedido mínimo'))->toBeNull()
        ->and(StoreMinimumOrder::label('1'))->toBe('1 peça')
        ->and(StoreMinimumOrder::label('10'))->toBe('10 peças');
});

it('normalizes social handles and complete urls', function (): void {
    expect(StoreSocialUrl::instagram('@minhaloja'))->toBe('https://instagram.com/minhaloja')
        ->and(StoreSocialUrl::facebook('facebook.com/minhaloja'))->toBe('https://facebook.com/minhaloja')
        ->and(StoreSocialUrl::tiktok('minhaloja'))->toBe('https://tiktok.com/@minhaloja')
        ->and(StoreSocialUrl::website('tanavitrine.com.br'))->toBe('https://tanavitrine.com.br')
        ->and(StoreSocialUrl::instagram('nome com espaço'))->toBeNull()
        ->and(StoreSocialUrl::instagram('https://example.com/minhaloja'))->toBeNull();
});
