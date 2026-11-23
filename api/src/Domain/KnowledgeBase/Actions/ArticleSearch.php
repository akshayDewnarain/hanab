<?php

namespace Domain\KnowledgeBase\Actions;

use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ArticleSearch
{
    /**
     * @return LengthAwarePaginator<int, KnowledgeArticle>
     */
    public function paginate(string $term, ?User $user, ?int $categoryId = null, ?string $tag = null): LengthAwarePaginator
    {
        $needle = mb_strtolower(trim($term));
        $like = '%'.addcslashes($needle, '%_\\').'%';
        $query = ArticleAccess::catalog(KnowledgeArticle::query(), $user)->with(['category', 'tags']);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        if ($tag !== null && $tag !== '') {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereRaw('LOWER(slug) = ?', [mb_strtolower($tag)]));
        }
        if ($needle !== '') {
            $query->where(function (Builder $match) use ($like) {
                $match->whereRaw('LOWER(title) like ? escape \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(summary, \'\')) like ? escape \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(search_text, \'\')) like ? escape \'\\\'', [$like])
                    ->orWhereHas('tags', fn (Builder $tags) => $tags->whereRaw('LOWER(name) like ? escape \'\\\'', [$like]))
                    ->orWhereHas('category', fn (Builder $category) => $category->whereRaw('LOWER(name) like ? escape \'\\\'', [$like]));
            })->orderByRaw(
                'CASE WHEN LOWER(title) like ? escape \'\\\' THEN 0 WHEN LOWER(COALESCE(summary, \'\')) like ? escape \'\\\' THEN 1 ELSE 2 END',
                [$like, $like],
            );
        }

        return $query->orderByDesc('updated_at')->paginate(15);
    }
}
