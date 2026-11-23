<?php

namespace Application\API\HelpCenter\Controllers;

use Application\API\HelpCenter\HelpResponse;
use Domain\Auth\Models\User;
use Domain\HelpCenter\Actions\HelpSearch;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpCategory;
use Domain\HelpCenter\Support\HelpAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpCatalogController
{
    public function contexts(Request $request): JsonResponse
    {
        abort_unless($request->user() instanceof User, 401);
        $contexts = config('help.contexts');
        $data = [];
        if (is_array($contexts)) {
            foreach ($contexts as $key => $value) {
                $path = is_array($value) ? ($value['feature_path'] ?? null) : null;
                if (! is_string($key) || ! is_string($path) || ! str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, '://')) {
                    continue;
                }
                $data[] = ['key' => $key, 'feature_path' => $path];
            }
        }

        return response()->json(['data' => $data]);
    }

    public function home(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $categories = HelpCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $counts = $this->counts($user);

        return response()->json([
            'data' => [
                'categories' => $categories->map(
                    fn (HelpCategory $category) => HelpResponse::category($category, $counts[$category->id] ?? 0),
                )->values(),
                'getting_started' => HelpAccess::catalog(HelpArticle::query(), $user)
                    ->with(['category', 'author'])
                    ->where('is_getting_started', true)
                    ->orderBy('sort_order')
                    ->limit(4)
                    ->get()
                    ->map(fn (HelpArticle $article) => HelpResponse::card($article)),
                'recent' => HelpAccess::catalog(HelpArticle::query(), $user)
                    ->with(['category', 'author'])
                    ->latest('updated_at')
                    ->limit(6)
                    ->get()
                    ->map(fn (HelpArticle $article) => HelpResponse::card($article)),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $article = HelpArticle::query()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        abort_unless($user instanceof User && HelpAccess::canRead($user, $article), 404);
        $article->load(['category', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => HelpResponse::article($article, $user)]);
    }

    public function search(Request $request, HelpSearch $search): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $term = trim((string) $request->query('q', ''));
        $page = $search->paginate(
            $term,
            $user,
            $request->filled('category_id') ? (int) $request->query('category_id') : null,
        );

        return response()->json([
            'data' => collect($page->items())->map(fn (HelpArticle $article) => HelpResponse::card($article, $term))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $key = trim((string) $request->query('key', ''));
        abort_unless($key !== '' && HelpAccess::registeredContext($key), 404);

        $articles = HelpAccess::catalog(HelpArticle::query(), $user)
            ->with(['category', 'author', 'attachments'])
            ->where('context_key', $key)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return response()->json([
            'data' => $articles->map(function (HelpArticle $article) use ($user) {
                $article->loadMissing('feedback');

                return HelpResponse::article($article, $user);
            })->values(),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function counts(User $user): array
    {
        return HelpAccess::catalog(HelpArticle::query(), $user)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
