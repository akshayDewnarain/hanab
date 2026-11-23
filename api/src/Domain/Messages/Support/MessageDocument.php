<?php

namespace Domain\Messages\Support;

use InvalidArgumentException;

/**
 * Allowlist for company-message documents produced by the shared rich-text editor.
 * Plain text that does not start with "{" is left unchanged.
 */
final class MessageDocument
{
    public const MAX_TEXT = 5000;

    private const MAX_DEPTH = 12;

    private const MAX_NODES = 2000;

    /** @var list<string> */
    private const BLOCKS = ['paragraph', 'heading', 'bulletList', 'orderedList', 'blockquote', 'callout', 'stepList', 'docStep', 'docImage', 'featureLink', 'horizontalRule'];

    public static function looksLikeDocument(string $body): bool
    {
        return str_starts_with(trim($body), '{');
    }

    public static function normalize(string $body, int $maxText = self::MAX_TEXT): ?string
    {
        if (! self::looksLikeDocument($body)) {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            $state = ['nodes' => 0, 'text' => 0, 'limit' => max(1, $maxText)];
            $document = self::node(is_array($decoded) ? $decoded : [], 'doc', 0, $state);

            return json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException) {
            return null;
        } catch (\JsonException) {
            return null;
        }
    }

    public static function plainText(string $body): string
    {
        if (! self::looksLikeDocument($body)) {
            return trim($body);
        }

        try {
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }

        if (! is_array($decoded)) {
            return '';
        }

        return trim(self::textOf($decoded));
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function node(array $node, string $expected, int $depth, array &$state): array
    {
        if ($depth > self::MAX_DEPTH || ++$state['nodes'] > self::MAX_NODES) {
            throw new InvalidArgumentException('The message content is too complex.');
        }

        $type = $node['type'] ?? null;
        if ($type !== $expected && $expected !== 'doc') {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        if (! is_string($type) || ($expected === 'doc' && $type !== 'doc')) {
            throw new InvalidArgumentException('The message content is not valid.');
        }

        self::assertKeys($node, ['type', 'content', 'text', 'marks', 'attrs']);

        return match ($type) {
            'doc' => self::parent($node, 'doc', self::BLOCKS, $depth, $state),
            'blockquote' => self::parent($node, 'blockquote', ['paragraph', 'heading', 'bulletList', 'orderedList'], $depth, $state),
            'callout' => self::callout($node, $depth, $state),
            'bulletList' => self::parent($node, 'bulletList', ['listItem'], $depth, $state),
            'orderedList' => self::orderedList($node, $depth, $state),
            'stepList' => self::parent($node, 'stepList', ['listItem'], $depth, $state),
            'docStep' => self::docStep($node, $depth, $state),
            'listItem' => self::parent($node, 'listItem', ['paragraph', 'heading', 'bulletList', 'orderedList'], $depth, $state),
            'docImage' => self::docImage($node, $state),
            'featureLink' => self::featureLink($node, $state),
            'horizontalRule' => self::horizontalRule($node),
            'paragraph' => self::inline($node, 'paragraph', $depth, $state),
            'heading' => self::heading($node, $depth, $state),
            'text' => self::text($node, $state),
            'hardBreak' => self::hardBreak($node),
            default => throw new InvalidArgumentException('The message content is not valid.'),
        };
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $allowed
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function parent(array $node, string $type, array $allowed, int $depth, array &$state): array
    {
        $content = self::children($node, $allowed, $depth, $state);

        return $content === [] ? ['type' => $type] : ['type' => $type, 'content' => $content];
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function orderedList(array $node, int $depth, array &$state): array
    {
        $normalized = self::parent($node, 'orderedList', ['listItem'], $depth, $state);
        $attrs = self::attrs($node, ['start', 'type']);
        if (array_key_exists('start', $attrs)) {
            $start = $attrs['start'];
            if (! is_int($start) || $start < 1 || $start > 9999) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            if ($start !== 1) {
                $normalized['attrs']['start'] = $start;
            }
        }
        if (array_key_exists('type', $attrs) && $attrs['type'] !== null) {
            if (! in_array($attrs['type'], ['1', 'a', 'A', 'i', 'I'], true)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            $normalized['attrs']['type'] = $attrs['type'];
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function callout(array $node, int $depth, array &$state): array
    {
        $attrs = self::attrs($node, ['kind']);
        $kind = $attrs['kind'] ?? 'note';
        if (! in_array($kind, ['note', 'tip', 'warning'], true)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $normalized = self::parent($node, 'callout', ['paragraph', 'heading', 'bulletList', 'orderedList'], $depth, $state);
        $normalized['attrs'] = ['kind' => $kind];

        return $normalized;
    }

    /**
     * A documentation image points at an uploaded attachment. Preview URLs are never stored.
     *
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function docImage(array $node, array &$state): array
    {
        if (array_key_exists('content', $node) || array_key_exists('text', $node) || array_key_exists('marks', $node)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $attrs = self::attrs($node, ['id', 'alt', 'caption', 'size', 'align', 'src']);
        $id = $attrs['id'] ?? null;
        if (is_string($id) && preg_match('/^[1-9][0-9]*$/', $id) === 1) {
            $id = (int) $id;
        }
        if (! is_int($id) || $id < 1) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $alt = $attrs['alt'] ?? null;
        if (! is_string($alt) || trim($alt) === '' || mb_strlen(trim($alt)) > 180) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $caption = $attrs['caption'] ?? null;
        if ($caption !== null && $caption !== '' && (! is_string($caption) || mb_strlen(trim($caption)) > 180)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $size = $attrs['size'] ?? 'md';
        if (! in_array($size, ['sm', 'md', 'lg'], true)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $align = $attrs['align'] ?? 'start';
        if (! in_array($align, ['start', 'center', 'end'], true)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $state['text'] += mb_strlen(trim($alt));
        $normalized = [
            'type' => 'docImage',
            'attrs' => [
                'id' => $id,
                'alt' => trim($alt),
                'size' => $size,
                'align' => $align,
            ],
        ];
        if (is_string($caption) && trim($caption) !== '') {
            $normalized['attrs']['caption'] = trim($caption);
            $state['text'] += mb_strlen(trim($caption));
        }

        return $normalized;
    }

    /**
     * A step keeps its title and explanation as editable text. The reader numbers it from its place in a sequence.
     *
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function docStep(array $node, int $depth, array &$state): array
    {
        $normalized = self::parent($node, 'docStep', ['paragraph', 'docImage'], $depth, $state);
        $paragraphs = 0;
        foreach ($normalized['content'] ?? [] as $child) {
            if (($child['type'] ?? null) === 'paragraph') {
                $paragraphs++;
            }
        }
        if ($paragraphs < 1) {
            throw new InvalidArgumentException('The message content is not valid.');
        }

        return $normalized;
    }

    /**
     * A feature link stores a registered context key. The application resolves that key to a destination.
     *
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function featureLink(array $node, array &$state): array
    {
        if (array_key_exists('content', $node) || array_key_exists('text', $node) || array_key_exists('marks', $node)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $attrs = self::attrs($node, ['label', 'description', 'contextKey']);
        $label = $attrs['label'] ?? null;
        if (! is_string($label) || trim($label) === '' || mb_strlen(trim($label)) > 80) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $description = $attrs['description'] ?? '';
        if (! is_string($description) || mb_strlen(trim($description)) > 180) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $key = $attrs['contextKey'] ?? null;
        if (! is_string($key) || preg_match('/^[a-z0-9]+(\.[a-z0-9-]+)*$/', trim($key)) !== 1 || strlen(trim($key)) > 80) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $state['text'] += mb_strlen(trim($label));
        $normalized = [
            'type' => 'featureLink',
            'attrs' => [
                'label' => trim($label),
                'contextKey' => trim($key),
            ],
        ];
        if (trim($description) !== '') {
            $normalized['attrs']['description'] = trim($description);
            $state['text'] += mb_strlen(trim($description));
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $node
     * @return array<string, mixed>
     */
    private static function horizontalRule(array $node): array
    {
        if (array_key_exists('content', $node) || array_key_exists('text', $node) || array_key_exists('marks', $node)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        self::attrs($node, []);

        return ['type' => 'horizontalRule'];
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function heading(array $node, int $depth, array &$state): array
    {
        $attrs = self::attrs($node, ['level']);
        $level = $attrs['level'] ?? null;
        if (! is_int($level) || $level < 2 || $level > 4) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $normalized = self::inline($node, 'heading', $depth, $state);
        $normalized['attrs'] = ['level' => $level];

        return $normalized;
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function inline(array $node, string $type, int $depth, array &$state): array
    {
        return self::parent($node, $type, ['text', 'hardBreak'], $depth, $state);
    }

    /**
     * @param  array<mixed>  $node
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return array<string, mixed>
     */
    private static function text(array $node, array &$state): array
    {
        if (array_key_exists('content', $node)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $text = $node['text'] ?? null;
        if (! is_string($text)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        $state['text'] += mb_strlen($text);
        if ($state['text'] > ($state['limit'] ?? self::MAX_TEXT)) {
            throw new InvalidArgumentException('The message content is too long.');
        }

        $normalized = ['type' => 'text', 'text' => $text];
        $marks = self::marks($node);
        if ($marks !== []) {
            $normalized['marks'] = $marks;
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $node
     * @return array<string, mixed>
     */
    private static function hardBreak(array $node): array
    {
        if (array_key_exists('content', $node) || array_key_exists('text', $node) || array_key_exists('marks', $node)) {
            throw new InvalidArgumentException('The message content is not valid.');
        }

        return ['type' => 'hardBreak'];
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $allowed
     * @param  array{nodes: int, text: int, limit?: int}  $state
     * @return list<array<string, mixed>>
     */
    private static function children(array $node, array $allowed, int $depth, array &$state): array
    {
        if (! array_key_exists('content', $node)) {
            return [];
        }
        if (! is_array($node['content'])) {
            throw new InvalidArgumentException('The message content is not valid.');
        }

        $children = [];
        foreach ($node['content'] as $child) {
            if (! is_array($child) || ! is_string($child['type'] ?? null) || ! in_array($child['type'], $allowed, true)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            $children[] = self::node($child, $child['type'], $depth + 1, $state);
        }

        return $children;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<string, mixed>>
     */
    private static function marks(array $node): array
    {
        if (! array_key_exists('marks', $node)) {
            return [];
        }
        if (! is_array($node['marks'])) {
            throw new InvalidArgumentException('The message content is not valid.');
        }

        $marks = [];
        foreach ($node['marks'] as $mark) {
            if (! is_array($mark) || ! is_string($mark['type'] ?? null)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            self::assertKeys($mark, ['type', 'attrs']);
            if (in_array($mark['type'], ['bold', 'italic', 'underline'], true)) {
                if (array_key_exists('attrs', $mark) && $mark['attrs'] !== [] && $mark['attrs'] !== null) {
                    throw new InvalidArgumentException('The message content is not valid.');
                }
                $marks[] = ['type' => $mark['type']];
                continue;
            }
            if ($mark['type'] !== 'link') {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            $attrs = self::attrs($mark, ['href', 'target', 'rel', 'class', 'title']);
            $href = $attrs['href'] ?? null;
            if (! is_string($href) || ! self::safeHref($href)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
            $marks[] = ['type' => 'link', 'attrs' => ['href' => trim($href)]];
        }

        return $marks;
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    private static function attrs(array $node, array $allowed): array
    {
        if (! array_key_exists('attrs', $node) || $node['attrs'] === null) {
            return [];
        }
        if (! is_array($node['attrs'])) {
            throw new InvalidArgumentException('The message content is not valid.');
        }
        foreach (array_keys($node['attrs']) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
        }

        return $node['attrs'];
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $allowed
     */
    private static function assertKeys(array $node, array $allowed): void
    {
        foreach (array_keys($node) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('The message content is not valid.');
            }
        }
    }

    /**
     * @param  array<mixed>  $node
     */
    private static function textOf(array $node): string
    {
        $type = $node['type'] ?? null;
        if ($type === 'text') {
            return is_string($node['text'] ?? null) ? $node['text'] : '';
        }
        if ($type === 'hardBreak') {
            return "\n";
        }
        if ($type === 'docImage') {
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $alt = is_string($attrs['alt'] ?? null) ? $attrs['alt'] : '';
            $caption = is_string($attrs['caption'] ?? null) ? $attrs['caption'] : '';
            $line = trim($alt.' '.$caption);

            return $line === '' ? '' : $line."\n";
        }
        if ($type === 'featureLink') {
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $label = is_string($attrs['label'] ?? null) ? $attrs['label'] : '';
            $description = is_string($attrs['description'] ?? null) ? $attrs['description'] : '';
            $line = trim($label.' '.$description);

            return $line === '' ? '' : $line."\n";
        }
        if ($type === 'horizontalRule') {
            return "\n";
        }
        $parts = '';
        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            if (is_array($child)) {
                $parts .= self::textOf($child);
            }
        }
        if (in_array($type, ['paragraph', 'heading'], true)) {
            return trim($parts)."\n";
        }

        return $parts;
    }

    private static function safeHref(string $href): bool
    {
        $value = trim($href);
        if ($value === '' || strlen($value) > 2000) {
            return false;
        }
        if (preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
            return false;
        }
        if (preg_match('/^(javascript|data|vbscript):/i', $value) === 1) {
            return false;
        }

        if (preg_match('#^/(?!/)#', $value) === 1 && ! str_contains($value, '..') && ! str_contains($value, '\\')) {
            return true;
        }

        return preg_match('#^(https?://|mailto:)#i', $value) === 1;
    }
}
