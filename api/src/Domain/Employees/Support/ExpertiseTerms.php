<?php

namespace Domain\Employees\Support;

final class ExpertiseTerms
{
    /**
     * Stored terms on a skill or certificate. A comma- or newline-separated string is the form shape.
     *
     * @return list<string>
     */
    public static function normalize(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\n,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $terms = [];
        foreach ($value as $term) {
            if (! is_string($term) && ! is_numeric($term)) {
                continue;
            }

            $term = trim((string) $term);
            if ($term === '') {
                continue;
            }

            $terms[mb_strtolower($term)] = $term;
        }

        return array_values($terms);
    }

    public static function stringify(mixed $value): string
    {
        return implode(', ', self::normalize($value));
    }

    public static function needle(string $term): string
    {
        $needle = mb_strtolower(trim($term));

        return trim(str_replace(['%', '_', '\\'], '', $needle));
    }

    public static function contains(string $haystack, string $term): bool
    {
        $needle = self::needle($term);
        if ($needle === '') {
            return false;
        }

        return str_contains(mb_strtolower($haystack), $needle);
    }

    /**
     * Name wins over code, and code wins over a synonym, so a canonical hit is not labeled as a synonym.
     *
     * @param  list<string>  $synonyms
     * @return array{matched_on: string, synonym: ?string}|null
     */
    public static function match(string $name, ?string $code, array $synonyms, string $term): ?array
    {
        if (self::contains($name, $term)) {
            return ['matched_on' => 'name', 'synonym' => null];
        }

        if ($code !== null && $code !== '' && self::contains($code, $term)) {
            return ['matched_on' => 'code', 'synonym' => null];
        }

        foreach (self::normalize($synonyms) as $synonym) {
            if (self::contains($synonym, $term)) {
                return ['matched_on' => 'synonym', 'synonym' => $synonym];
            }
        }

        return null;
    }
}
