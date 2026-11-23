<?php

namespace Application\API\KnowledgeBase\Controllers;

use Application\API\KnowledgeBase\KnowledgeResponse;
use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Models\KnowledgeCategory;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KnowledgeCategoryController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user() instanceof User, 401);
        $tallies = ArticleAccess::catalog(\Domain\KnowledgeBase\Models\KnowledgeArticle::query(), $request->user())
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $categories = KnowledgeCategory::query()
            ->when(! $request->user()?->can('manage knowledge categories'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (KnowledgeCategory $category) => KnowledgeResponse::category($category, (int) ($tallies[$category->id] ?? 0)))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $category = KnowledgeCategory::query()->create($this->validated($request));

        return response()->json(['data' => KnowledgeResponse::category($category, 0)], 201);
    }

    public function update(Request $request, KnowledgeCategory $category): JsonResponse
    {
        $category->fill($this->validated($request, $category))->save();

        return response()->json(['data' => KnowledgeResponse::category($category->fresh(), 0)]);
    }

    public function destroy(Request $request, KnowledgeCategory $category): JsonResponse
    {
        $this->manager($request);
        if ($category->articles()->exists() || $category->children()->exists()) {
            throw ValidationException::withMessages(['category' => 'Move the articles and child categories first.']);
        }
        $category->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?KnowledgeCategory $category = null): array
    {
        $this->manager($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:400'],
            'icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9:-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
        ]);
        $parentId = $data['parent_id'] ?? null;
        if ($category !== null && $parentId !== null) {
            $cursor = (int) $parentId;
            $guard = 0;
            while ($cursor && $guard < 20) {
                if ($cursor === (int) $category->id) {
                    throw ValidationException::withMessages(['parent_id' => 'A category cannot sit inside itself.']);
                }
                $cursor = (int) KnowledgeCategory::query()->whereKey($cursor)->value('parent_id');
                $guard++;
            }
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        if ($category === null) {
            $base = Str::slug($data['name']) ?: 'category';
            $slug = $base;
            $suffix = 2;
            while (KnowledgeCategory::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }
            $data['slug'] = $slug;
        }

        return $data;
    }

    private function manager(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('manage knowledge categories'), 403);

        return $user;
    }
}
