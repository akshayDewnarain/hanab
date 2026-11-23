<?php

namespace Application\API\KnowledgeBase;

use Domain\Auth\Models\User;
use Domain\Employees\Models\Employee;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Models\KnowledgeAttachment;
use Domain\KnowledgeBase\Models\KnowledgeCategory;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Support\Facades\DB;

final class KnowledgeResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function card(KnowledgeArticle $article, ?string $term = null): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'summary' => $article->summary,
            'snippet' => self::snippet($article, $term),
            'status' => $article->status,
            'visibility' => $article->visibility,
            'featured' => $article->featured,
            'updated_at' => $article->updated_at?->toAtomString(),
            'category' => $article->category === null ? null : [
                'id' => $article->category->id,
                'name' => $article->category->name,
                'slug' => $article->category->slug,
            ],
            'tags' => $article->tags->map(fn ($tag) => ['name' => $tag->name, 'slug' => $tag->slug])->values(),
            'author' => self::person($article->author),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(KnowledgeArticle $article, User $user): array
    {
        $document = json_decode($article->content, true);

        return [
            ...self::card($article),
            'content' => is_array($document) ? $document : ['type' => 'doc', 'content' => []],
            'version' => $article->version,
            'review_due_on' => $article->review_due_on?->toDateString(),
            'published_at' => $article->published_at?->toAtomString(),
            'can_edit' => $user->can('update knowledge articles') || (int) $article->author_id === (int) $user->id,
            'can_publish' => $user->can('publish knowledge articles'),
            'attachments' => $article->attachments->map(fn (KnowledgeAttachment $file) => [
                'id' => $file->id,
                'filename' => $file->filename,
                'mime' => $file->mime,
                'size' => $file->size,
            ])->values(),
            'skill_ids' => DB::table('knowledge_article_skill')->where('knowledge_article_id', $article->id)->pluck('skill_id')->map(fn ($id) => (int) $id)->values(),
            'certificate_ids' => DB::table('knowledge_article_certificate')->where('knowledge_article_id', $article->id)->pluck('certificate_id')->map(fn ($id) => (int) $id)->values(),
            'related' => self::related($article, $user),
            'colleagues' => self::colleagues($article),
            'feedback' => [
                'helpful' => $article->feedback()->where('helpful', true)->count(),
                'unhelpful' => $article->feedback()->where('helpful', false)->count(),
                'mine' => optional($article->feedback()->where('user_id', $user->id)->first())->helpful,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function category(KnowledgeCategory $category, int $count): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'parent_id' => $category->parent_id,
            'article_count' => $count,
        ];
    }

    private static function snippet(KnowledgeArticle $article, ?string $term): ?string
    {
        $text = trim((string) ($article->search_text ?: $article->summary));
        if ($text === '') {
            return null;
        }
        $needle = trim((string) $term);
        $start = $needle === '' ? 0 : (mb_stripos($text, $needle) ?: 0);
        $start = max(0, $start - 40);

        return mb_substr($text, $start, 180);
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private static function person(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'name' => $user->name];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function related(KnowledgeArticle $article, User $user): array
    {
        $tagIds = $article->tags->pluck('id');
        $query = ArticleAccess::catalog(KnowledgeArticle::query(), $user)
            ->with(['category', 'tags'])
            ->whereKeyNot($article->id)
            ->where(function ($match) use ($article, $tagIds) {
                if ($article->category_id !== null) {
                    $match->where('category_id', $article->category_id);
                }
                if ($tagIds->isNotEmpty()) {
                    $match->orWhereHas('tags', fn ($tags) => $tags->whereIn('knowledge_tags.id', $tagIds));
                }
            });

        if ($article->category_id === null && $tagIds->isEmpty()) {
            return [];
        }

        return $query->limit(4)->get()->map(fn (KnowledgeArticle $item) => self::card($item))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function colleagues(KnowledgeArticle $article): array
    {
        $skillIds = DB::table('knowledge_article_skill')->where('knowledge_article_id', $article->id)->pluck('skill_id');
        if ($skillIds->isEmpty()) {
            return [];
        }

        return Employee::query()
            ->with('employeeRole')
            ->where('is_active', true)
            ->whereHas('skills', fn ($skills) => $skills->whereIn('skills.id', $skillIds))
            ->limit(6)
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'role' => $employee->employeeRole?->name,
            ])
            ->all();
    }
}
