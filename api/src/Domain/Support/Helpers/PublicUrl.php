<?php

namespace Domain\Support\Helpers;

/**
 * Ensures media URLs work from the public booking app (separate origin from the API).
 */
final class PublicUrl
{
    public static function forMedia(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return mb_rtrim((string) config('app.url'), '/').'/'.mb_ltrim($url, '/');
    }
}
