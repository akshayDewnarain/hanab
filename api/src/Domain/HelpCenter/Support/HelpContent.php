<?php

namespace Domain\HelpCenter\Support;

use Domain\Messages\Support\MessageDocument;

/**
 * Article documents are ProseMirror JSON.
 *
 * Schema 2 adds documentation blocks beside the original rich text. Existing
 * nodes are unchanged, so older articles stay valid. Unknown node types are
 * rejected on save. The reader skips a node it cannot draw instead of injecting HTML.
 */
final class HelpContent
{
    public const SCHEMA = 2;

    public const MAX_TEXT = 40000;

    /**
     * @return array{document: string, text: string}|null
     */
    public static function prepare(mixed $value): ?array
    {
        $json = is_string($value) ? $value : (is_array($value) ? json_encode($value) : null);
        if (! is_string($json) || $json === '') {
            return null;
        }

        $document = MessageDocument::normalize($json, self::MAX_TEXT);
        if ($document === null || ! self::destinationsAllowed($document)) {
            return null;
        }

        return [
            'document' => $document,
            'text' => MessageDocument::plainText($document),
        ];
    }

    private static function destinationsAllowed(string $document): bool
    {
        $decoded = json_decode($document, true);
        if (! is_array($decoded)) {
            return false;
        }

        return self::walkDestinations($decoded);
    }

    /**
     * @param  array<mixed>  $node
     */
    private static function walkDestinations(array $node): bool
    {
        if (($node['type'] ?? null) === 'featureLink') {
            $key = $node['attrs']['contextKey'] ?? null;

            return is_string($key) && HelpAccess::registeredContext($key);
        }
        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            if (is_array($child) && ! self::walkDestinations($child)) {
                return false;
            }
        }

        return true;
    }
}
