<?php

namespace Domain\HelpCenter\Support;

use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\HelpCenter\Models\HelpArticle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deny-by-default help visibility.
 *
 * A public visibility value is only anonymous when the application explicitly
 * enables public access. Hanab leaves that switch off.
 */
final class HelpAccess
{
    public static function anonymousAllowed(): bool
    {
        return config('help.public_access_enabled') === true;
    }

    public static function canRead(?User $user, HelpArticle $article): bool
    {
        if ($user === null) {
            return self::anonymousAllowed()
                && $article->status === HelpArticle::STATUS_PUBLISHED
                && $article->visibility === HelpArticle::VISIBILITY_PUBLIC
                && $article->audience_permission === null;
        }

        if ($article->status !== HelpArticle::STATUS_PUBLISHED) {
            return self::maintains($user) || (int) $article->author_id === (int) $user->id;
        }

        if ($article->visibility === HelpArticle::VISIBILITY_PRIVATE) {
            return self::maintains($user)
                || (int) $article->author_id === (int) $user->id
                || (int) $article->editor_id === (int) $user->id;
        }

        if (! in_array($article->visibility, [
            HelpArticle::VISIBILITY_AUTHENTICATED,
            HelpArticle::VISIBILITY_PUBLIC,
        ], true)) {
            return false;
        }

        return self::audienceAllows($user, $article);
    }

    /**
     * @param  Builder<HelpArticle>  $query
     * @return Builder<HelpArticle>
     */
    public static function catalog(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            if (! self::anonymousAllowed()) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->where('status', HelpArticle::STATUS_PUBLISHED)
                ->where('visibility', HelpArticle::VISIBILITY_PUBLIC)
                ->whereNull('audience_permission');
        }

        $query->where('status', HelpArticle::STATUS_PUBLISHED)->where(function (Builder $visible) use ($user) {
            $visible->whereIn('visibility', [
                HelpArticle::VISIBILITY_AUTHENTICATED,
                HelpArticle::VISIBILITY_PUBLIC,
            ]);
            if (self::maintains($user)) {
                $visible->orWhere('visibility', HelpArticle::VISIBILITY_PRIVATE);
            }
        });

        if (! self::seesEveryAudience($user)) {
            $names = $user->getAllPermissions()->pluck('name')->all();
            $query->where(function (Builder $audience) use ($names) {
                $audience->whereNull('audience_permission');
                if ($names !== []) {
                    $audience->orWhereIn('audience_permission', $names);
                }
            });
        }

        return $query;
    }

    public static function maintains(User $user): bool
    {
        return $user->hasRole(BootstrapRoleNames::SUPER_ADMIN)
            || $user->can('update help articles')
            || $user->can('publish help articles');
    }

    public static function normalizeVisibility(string $visibility): string
    {
        if ($visibility === HelpArticle::VISIBILITY_PUBLIC && config('help.allow_public_visibility_selection') !== true) {
            return (string) config('help.default_visibility', HelpArticle::VISIBILITY_AUTHENTICATED);
        }

        return $visibility;
    }

    public static function registeredContext(string $key): bool
    {
        $contexts = config('help.contexts');
        if (! is_array($contexts) || $contexts === []) {
            return true;
        }

        return array_key_exists($key, $contexts);
    }

    private static function audienceAllows(User $user, HelpArticle $article): bool
    {
        if ($article->audience_permission === null || self::seesEveryAudience($user)) {
            return true;
        }

        return $user->can($article->audience_permission);
    }

    private static function seesEveryAudience(User $user): bool
    {
        return self::maintains($user);
    }
}
