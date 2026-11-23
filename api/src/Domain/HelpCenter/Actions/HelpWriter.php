<?php

namespace Domain\HelpCenter\Actions;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Exceptions\HelpConflictException;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Support\HelpAccess;
use Domain\HelpCenter\Support\HelpContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class HelpWriter
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $user, array $input): HelpArticle
    {
        $prepared = $this->content($input['content'] ?? null, false);

        return DB::transaction(function () use ($user, $input, $prepared) {
            $article = HelpArticle::query()->create([
                'category_id' => $input['category_id'] ?? null,
                'author_id' => $user->id,
                'editor_id' => $user->id,
                'title' => trim((string) $input['title']),
                'slug' => $this->slug((string) $input['title']),
                'summary' => $this->nullableString($input['summary'] ?? null),
                'content' => $prepared['document'],
                'search_text' => $prepared['text'],
                'keywords' => $this->nullableString($input['keywords'] ?? null),
                'status' => HelpArticle::STATUS_DRAFT,
                'visibility' => HelpAccess::normalizeVisibility((string) ($input['visibility'] ?? config('help.default_visibility'))),
                'context_key' => $this->contextKey($input['context_key'] ?? null),
                'audience_permission' => $this->nullableString($input['audience_permission'] ?? null),
                'feature_path' => $this->featurePath($input['feature_path'] ?? null),
                'app_version' => $this->nullableString($input['app_version'] ?? null),
                'sort_order' => (int) ($input['sort_order'] ?? 0),
                'is_getting_started' => (bool) ($input['is_getting_started'] ?? false),
                'version' => 1,
                'review_due_on' => $input['review_due_on'] ?? null,
            ]);
            $this->revise($article, $user, $input['note'] ?? null);

            return $article->fresh(['category', 'author', 'editor']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, HelpArticle $article, array $input): HelpArticle
    {
        return $this->change($user, $article, $input, false);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function publish(User $user, HelpArticle $article, array $input): HelpArticle
    {
        return $this->change($user, $article, $input, true);
    }

    public function archive(User $user, HelpArticle $article, int $version): HelpArticle
    {
        return DB::transaction(function () use ($user, $article, $version) {
            $locked = $this->lock($article, $version);
            $locked->status = HelpArticle::STATUS_ARCHIVED;
            $locked->editor_id = $user->id;
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked->fresh(['category', 'author', 'editor']);
        });
    }

    public function restore(User $user, HelpArticle $article, int $revisionNumber, int $version): HelpArticle
    {
        return DB::transaction(function () use ($user, $article, $revisionNumber, $version) {
            $locked = $this->lock($article, $version);
            $revision = $locked->revisions()->where('number', $revisionNumber)->firstOrFail();
            $prepared = HelpContent::prepare($revision->content);
            $locked->title = $revision->title;
            $locked->summary = $revision->summary;
            $locked->content = $revision->content;
            $locked->search_text = $prepared['text'] ?? '';
            $locked->editor_id = $user->id;
            $locked->version = $locked->version + 1;
            $locked->save();
            $this->revise($locked, $user, 'Restored revision '.$revisionNumber);

            return $locked->fresh(['category', 'author', 'editor']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function change(User $user, HelpArticle $article, array $input, bool $publish): HelpArticle
    {
        $requireText = $publish || $article->status === HelpArticle::STATUS_PUBLISHED;
        $prepared = $this->content($input['content'] ?? $article->content, $requireText);

        return DB::transaction(function () use ($user, $article, $input, $publish, $prepared) {
            $locked = $this->lock($article, (int) $input['version']);
            $locked->title = trim((string) ($input['title'] ?? $locked->title));
            $locked->summary = array_key_exists('summary', $input) ? $this->nullableString($input['summary']) : $locked->summary;
            $locked->content = $prepared['document'];
            $locked->search_text = $prepared['text'];
            $locked->keywords = array_key_exists('keywords', $input) ? $this->nullableString($input['keywords']) : $locked->keywords;
            $locked->category_id = array_key_exists('category_id', $input) ? $input['category_id'] : $locked->category_id;
            $locked->visibility = HelpAccess::normalizeVisibility((string) ($input['visibility'] ?? $locked->visibility));
            $locked->context_key = array_key_exists('context_key', $input) ? $this->contextKey($input['context_key']) : $locked->context_key;
            $locked->audience_permission = array_key_exists('audience_permission', $input) ? $this->nullableString($input['audience_permission']) : $locked->audience_permission;
            $locked->feature_path = array_key_exists('feature_path', $input) ? $this->featurePath($input['feature_path']) : $locked->feature_path;
            $locked->app_version = array_key_exists('app_version', $input) ? $this->nullableString($input['app_version']) : $locked->app_version;
            $locked->sort_order = array_key_exists('sort_order', $input) ? (int) $input['sort_order'] : $locked->sort_order;
            $locked->is_getting_started = array_key_exists('is_getting_started', $input) ? (bool) $input['is_getting_started'] : $locked->is_getting_started;
            $locked->review_due_on = array_key_exists('review_due_on', $input) ? $input['review_due_on'] : $locked->review_due_on;
            $locked->editor_id = $user->id;
            if ($publish) {
                $locked->status = HelpArticle::STATUS_PUBLISHED;
                $locked->published_at = $locked->published_at ?? now();
            }
            $locked->version = $locked->version + 1;
            $locked->save();
            if (config('help.revisions_enabled') !== false) {
                $this->revise($locked, $user, $input['note'] ?? null);
            }

            return $locked->fresh(['category', 'author', 'editor']);
        });
    }

    private function lock(HelpArticle $article, int $version): HelpArticle
    {
        $locked = HelpArticle::query()->whereKey($article->id)->lockForUpdate()->firstOrFail();
        if ((int) $locked->version !== $version) {
            throw new HelpConflictException('This guide was saved by someone else.');
        }

        return $locked;
    }

    /**
     * @return array{document: string, text: string}
     */
    private function content(mixed $value, bool $requireText): array
    {
        $prepared = HelpContent::prepare($value);
        if ($prepared === null || ($requireText && trim($prepared['text']) === '')) {
            throw ValidationException::withMessages([
                'content' => 'The guide content is not valid.',
            ]);
        }

        return $prepared;
    }

    private function contextKey(mixed $value): ?string
    {
        $key = $this->nullableString($value);
        if ($key === null) {
            return null;
        }
        if (! HelpAccess::registeredContext($key)) {
            throw ValidationException::withMessages([
                'context_key' => 'This help context is not registered.',
            ]);
        }

        return $key;
    }

    private function featurePath(mixed $value): ?string
    {
        $path = $this->nullableString($value);
        if ($path === null) {
            return null;
        }
        if (! str_starts_with($path, '/') || str_contains($path, '://') || str_contains($path, '..')) {
            throw ValidationException::withMessages([
                'feature_path' => 'The feature link must be an internal path.',
            ]);
        }

        return $path;
    }

    private function revise(HelpArticle $article, User $user, mixed $note): void
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
        $base = Str::slug($title) ?: 'guide';
        $slug = $base;
        $suffix = 2;
        while (HelpArticle::query()->where('slug', $slug)->exists()) {
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
