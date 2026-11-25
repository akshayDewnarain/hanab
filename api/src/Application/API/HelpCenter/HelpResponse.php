<?php

namespace Application\API\HelpCenter;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpCategory;
use Domain\HelpCenter\Support\HelpAccess;

final class HelpResponse
{
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, array<string, mixed>>  $previews
     * @return array<string, mixed>
     */
    public static function category(HelpCategory $category, int $count, array $previews = []): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'parent_id' => $category->parent_id,
            'is_active' => $category->is_active,
            'article_count' => $count,
            'previews' => $previews,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(HelpArticle $article, string $term = ''): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'summary' => $article->summary,
            'snippet' => self::snippet($article->search_text, $term),
            'status' => $article->status,
            'visibility' => $article->visibility,
            'context_key' => $article->context_key,
            'feature_path' => $article->feature_path,
            'sort_order' => $article->sort_order,
            'is_getting_started' => $article->is_getting_started,
            'updated_at' => $article->updated_at?->toIso8601String(),
            'category' => $article->category === null ? null : [
                'id' => $article->category->id,
                'name' => $article->category->name,
                'slug' => $article->category->slug,
            ],
            'author' => $article->author === null ? null : [
                'id' => $article->author->id,
                'name' => $article->author->name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(HelpArticle $article, User $user): array
    {
        $document = json_decode($article->content, true);
        $related = HelpAccess::catalog(HelpArticle::query(), $user)
            ->with(['category'])
            ->whereKeyNot($article->id)
            ->where(function ($query) use ($article) {
                if ($article->category_id !== null) {
                    $query->where('category_id', $article->category_id);
                }
                if ($article->context_key !== null) {
                    $query->orWhere('context_key', $article->context_key);
                }
            })
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $helpful = $article->relationLoaded('feedback') ? $article->feedback : $article->feedback()->get();

        return [
            ...self::card($article),
            'content' => is_array($document) ? $document : ['type' => 'doc', 'content' => []],
            'keywords' => $article->keywords,
            'audience_permission' => $article->audience_permission,
            'app_version' => $article->app_version,
            'version' => $article->version,
            'review_due_on' => $article->review_due_on?->toDateString(),
            'can_edit' => $user->can('update help articles') || (int) $article->author_id === (int) $user->id,
            'can_publish' => $user->can('publish help articles'),
            'attachments' => $article->attachments->map(fn ($file) => [
                'id' => $file->id,
                'filename' => $file->filename,
                'mime' => $file->mime,
                'size' => $file->size,
                'caption' => $file->caption,
                'alt_text' => $file->alt_text,
            ])->values(),
            'related' => $related->map(fn (HelpArticle $item) => self::card($item))->values(),
            'feedback' => [
                'helpful' => $helpful->where('helpful', true)->count(),
                'not_helpful' => $helpful->where('helpful', false)->count(),
                'mine' => optional($helpful->firstWhere('user_id', $user->id))->helpful,
            ],
        ];
    }

    private static function snippet(?string $text, string $term): ?string
    {
        $needle = mb_strtolower(trim($term));
        $plain = trim((string) $text);
        if ($needle === '' || $plain === '') {
            return null;
        }
        $position = mb_stripos($plain, $needle);
        if ($position === false) {
            return mb_substr($plain, 0, 180);
        }
        $start = max(0, $position - 40);

        return trim(mb_substr($plain, $start, 180));
    }
}
