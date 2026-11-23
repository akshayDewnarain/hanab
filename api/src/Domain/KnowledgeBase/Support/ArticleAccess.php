<?php

namespace Domain\KnowledgeBase\Support;

use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deny-by-default article visibility.
 *
 * A public visibility value is only anonymous when the application explicitly
 * enables public access. Hanab leaves that switch off.
 */
final class ArticleAccess
{
    public static function anonymousAllowed(): bool
    {
        return config('knowledge.public_access_enabled') === true;
    }

    public static function canRead(?User $user, KnowledgeArticle $article): bool
    {
        if ($user === null) {
            return self::anonymousAllowed()
                && $article->status === KnowledgeArticle::STATUS_PUBLISHED
                && $article->visibility === KnowledgeArticle::VISIBILITY_PUBLIC;
        }

        if ($article->status !== KnowledgeArticle::STATUS_PUBLISHED) {
            return self::maintains($user) || (int) $article->author_id === (int) $user->id;
        }

        if ($article->visibility === KnowledgeArticle::VISIBILITY_PRIVATE) {
            return self::maintains($user)
                || (int) $article->author_id === (int) $user->id
                || (int) $article->editor_id === (int) $user->id;
        }

        return in_array($article->visibility, [
            KnowledgeArticle::VISIBILITY_AUTHENTICATED,
            KnowledgeArticle::VISIBILITY_PUBLIC,
        ], true);
    }

    /**
     * Published articles a reader may discover. Drafts and archives stay out.
     *
     * @param  Builder<KnowledgeArticle>  $query
     * @return Builder<KnowledgeArticle>
     */
    public static function catalog(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            if (! self::anonymousAllowed()) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->where('status', KnowledgeArticle::STATUS_PUBLISHED)
                ->where('visibility', KnowledgeArticle::VISIBILITY_PUBLIC);
        }

        return $query->where('status', KnowledgeArticle::STATUS_PUBLISHED)->where(function (Builder $visible) use ($user) {
            $visible->whereIn('visibility', [
                KnowledgeArticle::VISIBILITY_AUTHENTICATED,
                KnowledgeArticle::VISIBILITY_PUBLIC,
            ]);
            if (self::maintains($user)) {
                $visible->orWhere('visibility', KnowledgeArticle::VISIBILITY_PRIVATE);
            }
        });
    }

    public static function maintains(User $user): bool
    {
        return $user->can('update knowledge articles') || $user->can('publish knowledge articles');
    }

    public static function normalizeVisibility(string $visibility): string
    {
        if ($visibility === KnowledgeArticle::VISIBILITY_PUBLIC && config('knowledge.allow_public_visibility_selection') !== true) {
            return (string) config('knowledge.default_visibility', KnowledgeArticle::VISIBILITY_AUTHENTICATED);
        }

        return $visibility;
    }
}
