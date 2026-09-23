<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;

final class StoreMinimumOrder
{
    public static function pieces(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        $normalized = (is_scalar($value) || $value instanceof Stringable)
            ? trim((string) $value)
            : '';
        if ($normalized === '' || ! preg_match('/\d[\d.\s]*/u', $normalized, $matches)) {
            return null;
        }

        $pieces = (int) preg_replace('/\D/u', '', $matches[0]);

        return $pieces > 0 ? $pieces : null;
    }

    public static function label(mixed $value): ?string
    {
        $pieces = self::pieces($value);
        if ($pieces === null) {
            return null;
        }

        return $pieces === 1 ? '1 peça' : "{$pieces} peças";
    }
}
