<?php

namespace Application\API\KnowledgeBase\Controllers;

use Application\API\KnowledgeBase\KnowledgeResponse;
use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Actions\ArticleSearch;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Models\KnowledgeCategory;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeCatalogController
{
    public function home(Request $request): JsonResponse
    {
        $user = $request->user();
        $categories = KnowledgeCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $counts = $this->counts($user);

        return response()->json([
            'data' => [
                'categories' => $categories->map(function (KnowledgeCategory $category) use ($counts) {
                    $card = KnowledgeResponse::category($category, $counts[$category->id] ?? 0);
                    $card['children'] = $category->children->map(
                        fn (KnowledgeCategory $child) => KnowledgeResponse::category($child, $counts[$child->id] ?? 0),
                    )->values();

                    return $card;
                })->values(),
                'featured' => ArticleAccess::catalog(KnowledgeArticle::query(), $user)
                    ->with(['category', 'tags', 'author'])
                    ->where('featured', true)
                    ->latest('updated_at')
                    ->limit(4)
                    ->get()
                    ->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
                'recent' => ArticleAccess::catalog(KnowledgeArticle::query(), $user)
                    ->with(['category', 'tags', 'author'])
                    ->latest('updated_at')
                    ->limit(6)
                    ->get()
                    ->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $article = KnowledgeArticle::query()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        abort_unless($user instanceof User && ArticleAccess::canRead($user, $article), 404);
        $article->load(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => KnowledgeResponse::article($article, $user)]);
    }

    public function search(Request $request, ArticleSearch $search): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $term = trim((string) $request->query('q', ''));
        $page = $search->paginate(
            $term,
            $user,
            $request->filled('category_id') ? (int) $request->query('category_id') : null,
            $request->filled('tag') ? (string) $request->query('tag') : null,
        );

        return response()->json([
            'data' => collect($page->items())->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article, $term))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function counts(?User $user): array
    {
        return ArticleAccess::catalog(KnowledgeArticle::query(), $user)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
