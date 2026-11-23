<?php

namespace Application\API\HelpCenter\Controllers;

use Application\API\HelpCenter\HelpResponse;
use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpCategory;
use Domain\HelpCenter\Support\HelpAccess;
use Domain\HelpCenter\Models\HelpArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HelpCategoryController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $query = HelpCategory::query()->orderBy('sort_order')->orderBy('name');
        if (! $user->can('manage help categories')) {
            $query->where('is_active', true);
        }
        $counts = HelpAccess::catalog(HelpArticle::query(), $user)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        return response()->json([
            'data' => $query->get()->map(
                fn (HelpCategory $category) => HelpResponse::category($category, (int) ($counts[$category->id] ?? 0)),
            )->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('manage help categories'), 403);
        $data = $this->validated($request);
        $category = HelpCategory::query()->create([
            ...$data,
            'slug' => $this->slug($data['name']),
        ]);

        return response()->json(['data' => HelpResponse::category($category, 0)], 201);
    }

    public function update(Request $request, HelpCategory $category): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('manage help categories'), 403);
        $category->fill($this->validated($request, $category));
        $category->save();

        return response()->json(['data' => HelpResponse::category($category, $category->articles()->count())]);
    }

    public function destroy(Request $request, HelpCategory $category): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('manage help categories'), 403);
        if ($category->articles()->exists() || $category->children()->exists()) {
            return response()->json(['message' => 'Move the guides in this category first.'], 422);
        }
        $category->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?HelpCategory $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9:-]+$/'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer', Rule::exists('help_categories', 'id')],
        ]);
        if (array_key_exists('parent_id', $data) && $this->cycles($current, $data['parent_id'])) {
            abort(422, 'A category cannot contain itself.');
        }

        return $data;
    }

    private function cycles(?HelpCategory $category, mixed $parentId): bool
    {
        if ($category === null || $parentId === null) {
            return false;
        }
        $cursor = (int) $parentId;
        $guard = 0;
        while ($cursor !== 0 && $guard < 20) {
            if ($cursor === (int) $category->id) {
                return true;
            }
            $cursor = (int) HelpCategory::query()->whereKey($cursor)->value('parent_id');
            $guard++;
        }

        return false;
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;
        while (HelpCategory::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
