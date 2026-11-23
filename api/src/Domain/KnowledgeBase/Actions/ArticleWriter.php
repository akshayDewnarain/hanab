<?php

namespace Domain\KnowledgeBase\Actions;

use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Exceptions\ArticleConflictException;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Models\KnowledgeTag;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Domain\KnowledgeBase\Support\ArticleContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ArticleWriter
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): KnowledgeArticle
    {
        $prepared = $this->content($input['content'] ?? null, false);

        return DB::transaction(function () use ($user, $input, $prepared) {
            $article = KnowledgeArticle::query()->create([
                'category_id' => $input['category_id'] ?? null,
                'author_id' => $user->id,
                'editor_id' => $user->id,
                'title' => trim((string) $input['title']),
                'slug' => $this->slug((string) $input['title']),
                'summary' => $this->nullableString($input['summary'] ?? null),
                'content' => $prepared['document'],
                'search_text' => $prepared['text'],
                'status' => KnowledgeArticle::STATUS_DRAFT,
                'visibility' => ArticleAccess::normalizeVisibility((string) ($input['visibility'] ?? config('knowledge.default_visibility'))),
                'featured' => (bool) ($input['featured'] ?? false),
                'version' => 1,
                'review_due_on' => $input['review_due_on'] ?? null,
            ]);
            $this->syncTags($article, $input['tags'] ?? []);
            $this->revise($article, $user, $input['note'] ?? null);

            return $article->fresh(['category', 'tags', 'author', 'editor']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, KnowledgeArticle $article, array $input): KnowledgeArticle
    {
        return $this->change($user, $article, $input, false);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function publish(User $user, KnowledgeArticle $article, array $input): KnowledgeArticle
    {
        return $this->change($user, $article, $input, true);
    }

    public function archive(User $user, KnowledgeArticle $article, int $version): KnowledgeArticle
    {
        return DB::transaction(function () use ($user, $article, $version) {
            $locked = $this->lock($article, $version);
            $locked->status = KnowledgeArticle::STATUS_ARCHIVED;
            $locked->editor_id = $user->id;
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked->fresh(['category', 'tags', 'author', 'editor']);
        });
    }

    public function restore(User $user, KnowledgeArticle $article, int $revisionNumber, int $version): KnowledgeArticle
    {
        return DB::transaction(function () use ($user, $article, $revisionNumber, $version) {
            $locked = $this->lock($article, $version);
            $revision = $locked->revisions()->where('number', $revisionNumber)->firstOrFail();
            $locked->title = $revision->title;
            $locked->summary = $revision->summary;
            $locked->content = $revision->content;
            $locked->search_text = ArticleContent::prepare($revision->content)['text'] ?? '';
            $locked->editor_id = $user->id;
            $locked->version = $locked->version + 1;
            $locked->save();
            $this->revise($locked, $user, 'Restored revision '.$revisionNumber);

            return $locked->fresh(['category', 'tags', 'author', 'editor']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function change(User $user, KnowledgeArticle $article, array $input, bool $publish): KnowledgeArticle
    {
        $requireText = $publish || $article->status === KnowledgeArticle::STATUS_PUBLISHED;
        $prepared = $this->content($input['content'] ?? $article->content, $requireText);

        return DB::transaction(function () use ($user, $article, $input, $publish, $prepared) {
            $locked = $this->lock($article, (int) $input['version']);
            $locked->title = trim((string) ($input['title'] ?? $locked->title));
            $locked->summary = array_key_exists('summary', $input) ? $this->nullableString($input['summary']) : $locked->summary;
            $locked->content = $prepared['document'];
            $locked->search_text = $prepared['text'];
            $locked->category_id = array_key_exists('category_id', $input) ? $input['category_id'] : $locked->category_id;
            $locked->visibility = ArticleAccess::normalizeVisibility((string) ($input['visibility'] ?? $locked->visibility));
            $locked->featured = array_key_exists('featured', $input) ? (bool) $input['featured'] : $locked->featured;
            $locked->review_due_on = array_key_exists('review_due_on', $input) ? $input['review_due_on'] : $locked->review_due_on;
            $locked->editor_id = $user->id;
            if ($publish) {
                $locked->status = KnowledgeArticle::STATUS_PUBLISHED;
                $locked->published_at = $locked->published_at ?? now();
            }
            $locked->version = $locked->version + 1;
            $locked->save();
            if (array_key_exists('tags', $input)) {
                $this->syncTags($locked, $input['tags'] ?? []);
            }
            if (config('knowledge.revisions_enabled') !== false) {
                $this->revise($locked, $user, $input['note'] ?? null);
            }

            return $locked->fresh(['category', 'tags', 'author', 'editor']);
        });
    }

    private function lock(KnowledgeArticle $article, int $version): KnowledgeArticle
    {
        $locked = KnowledgeArticle::query()->whereKey($article->id)->lockForUpdate()->firstOrFail();
        if ((int) $locked->version !== $version) {
            throw new ArticleConflictException('This article was saved by someone else.');
        }

        return $locked;
    }

    /**
     * @return array{document: string, text: string}
     */
    private function content(mixed $value, bool $requireText): array
    {
        $prepared = ArticleContent::prepare($value);
        if ($prepared === null || ($requireText && trim($prepared['text']) === '')) {
            throw ValidationException::withMessages([
                'content' => 'The article content is not valid.',
            ]);
        }

        return $prepared;
    }

    /**
     * @param  mixed  $tags
     */
    private function syncTags(KnowledgeArticle $article, mixed $tags): void
    {
        $names = collect(is_array($tags) ? $tags : [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
            ->take(12)
            ->values();

        $ids = $names->map(function (string $name) {
            $slug = Str::slug($name) ?: 'tag';
            $tag = KnowledgeTag::query()->where('slug', $slug)->first();
            if ($tag === null) {
                $tag = KnowledgeTag::query()->create(['name' => $name, 'slug' => $this->uniqueTagSlug($slug)]);
            }

            return $tag->id;
        });

        $article->tags()->sync($ids->all());
    }

    private function revise(KnowledgeArticle $article, User $user, mixed $note): void
    {
        $number = ((int) $article->revisions()->max('number')) + 1;
        $article->revisions()->create([
            'number' => $number,
            'title' => $article->title,
            'summary' => $article->summary,
            'content' => $article->content,
            'editor_id' => $user->id,
            'note' => $this->nullableString($note),
        ]);
    }

    private function slug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $suffix = 2;
        while (KnowledgeArticle::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function uniqueTagSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;
        while (KnowledgeTag::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
