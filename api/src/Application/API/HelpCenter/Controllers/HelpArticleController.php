<?php

namespace Application\API\HelpCenter\Controllers;

use Application\API\HelpCenter\HelpResponse;
use Application\API\HelpCenter\Requests\SaveHelpArticleRequest;
use Domain\Auth\Models\User;
use Domain\HelpCenter\Actions\HelpWriter;
use Domain\HelpCenter\Exceptions\HelpConflictException;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpArticleFeedback;
use Domain\HelpCenter\Support\HelpAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpArticleController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->editor($request);
        $query = HelpArticle::query()->with(['category', 'author'])->latest('updated_at');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->query('category_id'));
        }

        return response()->json([
            'data' => $query->limit(100)->get()->map(fn (HelpArticle $article) => HelpResponse::card($article))->values(),
        ]);
    }

    public function show(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $this->editor($request);
        abort_unless(HelpAccess::canRead($user, $article) || HelpAccess::maintains($user), 404);
        $article->load(['category', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => HelpResponse::article($article, $user)]);
    }

    public function store(SaveHelpArticleRequest $request, HelpWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('create help articles'), 403);

        return response()->json([
            'data' => HelpResponse::article($writer->create($user, $request->validated())->load(['category', 'author', 'editor', 'attachments', 'feedback']), $user),
        ], 201);
    }

    public function update(SaveHelpArticleRequest $request, HelpArticle $article, HelpWriter $writer): JsonResponse
    {
        return $this->save($request, $article, $writer, false);
    }

    public function publish(SaveHelpArticleRequest $request, HelpArticle $article, HelpWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('publish help articles'), 403);

        return $this->save($request, $article, $writer, true);
    }

    public function archive(Request $request, HelpArticle $article, HelpWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('publish help articles'), 403);
        $version = (int) $request->validate(['version' => ['required', 'integer', 'min:1']])['version'];
        try {
            $saved = $writer->archive($user, $article, $version);
        } catch (HelpConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => HelpResponse::card($saved->load(['category', 'author']))]);
    }

    public function destroy(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('delete help articles'), 403);
        $article->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function revisions(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('view help revisions'), 403);

        return response()->json([
            'data' => $article->revisions()->with('editor')->get()->map(fn ($revision) => [
                'number' => $revision->number,
                'title' => $revision->title,
                'summary' => $revision->summary,
                'note' => $revision->note,
                'created_at' => $revision->created_at?->toIso8601String(),
                'editor' => $revision->editor === null ? null : ['id' => $revision->editor->id, 'name' => $revision->editor->name],
            ])->values(),
        ]);
    }

    public function restore(Request $request, HelpArticle $article, int $number, HelpWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('restore help revisions'), 403);
        $version = (int) $request->validate(['version' => ['required', 'integer', 'min:1']])['version'];
        try {
            $saved = $writer->restore($user, $article, $number, $version);
        } catch (HelpConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
        $saved->load(['category', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => HelpResponse::article($saved, $user)]);
    }

    public function feedback(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && HelpAccess::canRead($user, $article), 404);
        abort_unless($article->status === HelpArticle::STATUS_PUBLISHED, 422);
        $helpful = (bool) $request->validate(['helpful' => ['required', 'boolean']])['helpful'];
        HelpArticleFeedback::query()->updateOrCreate(
            ['help_article_id' => $article->id, 'user_id' => $user->id],
            ['helpful' => $helpful],
        );

        return response()->json(['data' => ['helpful' => $helpful]]);
    }

    private function save(SaveHelpArticleRequest $request, HelpArticle $article, HelpWriter $writer, bool $publish): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($publish || $user->can('update help articles')), 403);
        try {
            $saved = $publish
                ? $writer->publish($user, $article, $request->validated())
                : $writer->update($user, $article, $request->validated());
        } catch (HelpConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
        $saved->load(['category', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => HelpResponse::article($saved, $user)]);
    }

    private function editor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('update help articles'), 403);

        return $user;
    }
}
