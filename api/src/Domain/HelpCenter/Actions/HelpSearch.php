<?php

namespace Domain\HelpCenter\Actions;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Support\HelpAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HelpSearch
{
    /**
     * @return LengthAwarePaginator<int, HelpArticle>
     */
    public function paginate(string $term, ?User $user, ?int $categoryId = null): LengthAwarePaginator
    {
        $needle = mb_strtolower(trim($term));
        $like = '%'.addcslashes($needle, '%_\\').'%';
        $query = HelpAccess::catalog(HelpArticle::query(), $user)->with(['category', 'author']);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        if ($needle !== '') {
            $query->where(function (Builder $match) use ($like) {
                $match->whereRaw('LOWER(title) like ? escape \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(summary, \'\')) like ? escape \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(search_text, \'\')) like ? escape \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(keywords, \'\')) like ? escape \'\\\'', [$like])
                    ->orWhereHas('category', fn (Builder $category) => $category->whereRaw('LOWER(name) like ? escape \'\\\'', [$like]));
            })->orderByRaw(
                'CASE WHEN LOWER(title) like ? escape \'\\\' THEN 0 WHEN LOWER(COALESCE(summary, \'\')) like ? escape \'\\\' THEN 1 ELSE 2 END',
                [$like, $like],
            );
        }

        return $query->orderBy('sort_order')->orderByDesc('updated_at')->paginate(15);
    }
}
