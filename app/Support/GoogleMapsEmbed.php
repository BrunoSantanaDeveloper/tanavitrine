<?php

declare(strict_types=1);

namespace App\Support;

final class GoogleMapsEmbed
{
    public static function sanitize(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $candidate = $raw;

        if (stripos($raw, '<iframe') !== false) {
            if (!preg_match('/src=["\']([^"\']+)["\']/i', $raw, $matches)) {
                return null;
            }
            $candidate = html_entity_decode(trim($matches[1]));
        }

        if (!str_starts_with($candidate, 'https://')) {
            return null;
        }

        $parts = parse_url($candidate);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $path = (string) $parts['path'];

        $isGoogleHost = $host === 'google.com'
            || $host === 'www.google.com'
            || str_ends_with($host, '.google.com');

        if (!$isGoogleHost) {
            return null;
        }

        if (!str_starts_with($path, '/maps/embed')) {
            return null;
        }

        return $candidate;
    }
}

