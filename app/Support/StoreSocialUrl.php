<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;

final class StoreSocialUrl
{
    public static function website(mixed $value): ?string
    {
        $value = self::stringValue($value);
        if ($value === '') {
            return null;
        }

        $url = preg_match('#^https?://#i', $value) ? $value : 'https://'.$value;

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    public static function instagram(mixed $value): ?string
    {
        return self::social($value, 'instagram.com', false);
    }

    public static function facebook(mixed $value): ?string
    {
        return self::social($value, 'facebook.com', false);
    }

    public static function tiktok(mixed $value): ?string
    {
        return self::social($value, 'tiktok.com', true);
    }

    private static function social(mixed $value, string $domain, bool $requiresAt): ?string
    {
        $value = self::stringValue($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return self::isExpectedHost($value, $domain) ? $value : null;
        }

        if (preg_match('#^(?:www\.)?'.preg_quote($domain, '#').'/#i', $value)) {
            $url = 'https://'.$value;

            return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
        }

        $handle = trim($value, " \t\n\r\0\x0B/@");
        if ($handle === '' || ! preg_match('/^[\pL\pN._-]+$/u', $handle)) {
            return null;
        }

        $prefix = $requiresAt ? '@' : '';

        return "https://{$domain}/{$prefix}{$handle}";
    }

    private static function isExpectedHost(string $url, string $domain): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsedHost = parse_url($url, PHP_URL_HOST);
        if (! is_string($parsedHost)) {
            return false;
        }

        $host = mb_strtolower($parsedHost);

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }

    private static function stringValue(mixed $value): string
    {
        return (is_scalar($value) || $value instanceof Stringable)
            ? trim((string) $value)
            : '';
    }
}
