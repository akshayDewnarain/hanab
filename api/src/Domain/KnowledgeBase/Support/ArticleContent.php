<?php

namespace Domain\KnowledgeBase\Support;

use Domain\Messages\Support\MessageDocument;

/** Stores the same rich-text document the shared editor emits. */
final class ArticleContent
{
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
        if ($document === null) {
            return null;
        }

        return [
            'document' => $document,
            'text' => MessageDocument::plainText($document),
        ];
    }
}
