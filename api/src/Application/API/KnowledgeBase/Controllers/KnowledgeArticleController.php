<?php

namespace Application\API\KnowledgeBase\Controllers;

use Application\API\KnowledgeBase\KnowledgeResponse;
use Application\API\KnowledgeBase\Requests\SaveArticleRequest;
use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Actions\ArticleWriter;
use Domain\KnowledgeBase\Exceptions\ArticleConflictException;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Models\KnowledgeArticleFeedback;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KnowledgeArticleController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->editor($request);
        $query = KnowledgeArticle::query()->with(['category', 'tags', 'author']);
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->query('category_id'));
        }
        if ($visibility = $request->query('visibility')) {
            $query->where('visibility', $visibility);
        }
        if ($tag = $request->query('tag')) {
            $query->whereHas('tags', fn ($tags) => $tags->where('slug', $tag));
        }

        $page = $query->latest('updated_at')->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ArticleAccess::canRead($user, $article), 404);
        $article->load(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']);

        return response()->json(['data' => KnowledgeResponse::article($article, $user)]);
    }

    public function store(SaveArticleRequest $request, ArticleWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('create knowledge articles'), 403);
        $article = $writer->create($user, $request->validated());
        $this->syncLinks($article, $request);

        return response()->json(['data' => KnowledgeResponse::article($article->fresh(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']), $user)], 201);
    }

    public function update(SaveArticleRequest $request, KnowledgeArticle $article, ArticleWriter $writer): JsonResponse
    {
        $user = $this->editor($request);
        try {
            $saved = $writer->update($user, $article, $request->validated());
        } catch (ArticleConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
        $this->syncLinks($saved, $request);

        return response()->json(['data' => KnowledgeResponse::article($saved->fresh(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']), $user)]);
    }

    public function publish(SaveArticleRequest $request, KnowledgeArticle $article, ArticleWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('publish knowledge articles'), 403);
        try {
            $saved = $writer->publish($user, $article, $request->validated());
        } catch (ArticleConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
        $this->syncLinks($saved, $request);

        return response()->json(['data' => KnowledgeResponse::article($saved->fresh(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']), $user)]);
    }

    public function archive(Request $request, KnowledgeArticle $article, ArticleWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('publish knowledge articles'), 403);
        $version = (int) $request->input('version');
        try {
            $saved = $writer->archive($user, $article, $version);
        } catch (ArticleConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => KnowledgeResponse::card($saved)]);
    }

    public function destroy(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('delete knowledge articles'), 403);
        $article->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function revisions(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->can('view knowledge revisions') || ArticleAccess::maintains($user)), 403);
        abort_unless(ArticleAccess::canRead($user, $article), 404);

        return response()->json([
            'data' => $article->revisions()->with('editor')->get()->map(fn ($revision) => [
                'number' => $revision->number,
                'title' => $revision->title,
                'summary' => $revision->summary,
                'note' => $revision->note,
                'created_at' => $revision->created_at?->toAtomString(),
                'editor' => $revision->editor === null ? null : ['id' => $revision->editor->id, 'name' => $revision->editor->name],
            ])->values(),
        ]);
    }

    public function revision(Request $request, KnowledgeArticle $article, int $number): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->can('view knowledge revisions') || ArticleAccess::maintains($user)), 403);
        $revision = $article->revisions()->where('number', $number)->firstOrFail();
        $document = json_decode($revision->content, true);

        return response()->json([
            'data' => [
                'number' => $revision->number,
                'title' => $revision->title,
                'summary' => $revision->summary,
                'content' => is_array($document) ? $document : ['type' => 'doc', 'content' => []],
                'note' => $revision->note,
            ],
        ]);
    }

    public function restore(Request $request, KnowledgeArticle $article, int $number, ArticleWriter $writer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('restore knowledge revisions'), 403);
        try {
            $saved = $writer->restore($user, $article, $number, (int) $request->input('version'));
        } catch (ArticleConflictException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => KnowledgeResponse::article($saved->fresh(['category', 'tags', 'author', 'editor', 'attachments', 'feedback']), $user)]);
    }

    public function feedback(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ArticleAccess::canRead($user, $article) && $article->status === KnowledgeArticle::STATUS_PUBLISHED, 404);
        $helpful = $request->boolean('helpful');
        if (! $request->exists('helpful')) {
            throw ValidationException::withMessages(['helpful' => 'Choose whether the article helped.']);
        }
        KnowledgeArticleFeedback::query()->updateOrCreate(
            ['knowledge_article_id' => $article->id, 'user_id' => $user->id],
            ['helpful' => $helpful],
        );

        return response()->json(['data' => ['helpful' => $helpful]]);
    }

    public function maintenance(Request $request): JsonResponse
    {
        $user = $this->editor($request);
        $base = KnowledgeArticle::query()->with(['category', 'tags', 'author']);

        return response()->json([
            'data' => [
                'overdue' => (clone $base)->where('status', KnowledgeArticle::STATUS_PUBLISHED)->whereDate('review_due_on', '<', now()->toDateString())->latest('review_due_on')->limit(20)->get()->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
                'drafts' => (clone $base)->where('status', KnowledgeArticle::STATUS_DRAFT)->latest('updated_at')->limit(20)->get()->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
                'recent' => (clone $base)->latest('updated_at')->limit(10)->get()->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
                'negative' => (clone $base)->whereHas('feedback', fn ($feedback) => $feedback->where('helpful', false))->latest('updated_at')->limit(20)->get()->map(fn (KnowledgeArticle $article) => KnowledgeResponse::card($article)),
            ],
        ]);
    }

    private function editor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('update knowledge articles'), 403);

        return $user;
    }

    private function syncLinks(KnowledgeArticle $article, Request $request): void
    {
        if ($request->exists('skill_ids')) {
            $ids = collect($request->input('skill_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();
            DB::table('knowledge_article_skill')->where('knowledge_article_id', $article->id)->delete();
            if ($ids->isNotEmpty()) {
                DB::table('knowledge_article_skill')->insert($ids->map(fn (int $id) => [
                    'knowledge_article_id' => $article->id,
                    'skill_id' => $id,
                ])->all());
            }
        }
        if ($request->exists('certificate_ids')) {
            $ids = collect($request->input('certificate_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();
            DB::table('knowledge_article_certificate')->where('knowledge_article_id', $article->id)->delete();
            if ($ids->isNotEmpty()) {
                DB::table('knowledge_article_certificate')->insert($ids->map(fn (int $id) => [
                    'knowledge_article_id' => $article->id,
                    'certificate_id' => $id,
                ])->all());
            }
        }
    }
}
