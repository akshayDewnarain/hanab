<?php

namespace Application\API\KnowledgeBase\Controllers;

use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Domain\KnowledgeBase\Models\KnowledgeAttachment;
use Domain\KnowledgeBase\Support\ArticleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KnowledgeAttachmentController
{
    public function store(Request $request, KnowledgeArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless(config('knowledge.attachments_enabled') === true, 404);
        abort_unless($user instanceof User && $user->can('manage knowledge attachments'), 403);
        abort_unless(ArticleAccess::canRead($user, $article) || ArticleAccess::maintains($user), 404);

        $extensions = config('knowledge.attachment_extensions');
        $file = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('knowledge.max_attachment_kilobytes')],
        ])['file'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (! is_array($extensions) || ! in_array($extension, $extensions, true)) {
            return response()->json(['message' => 'This file type cannot be attached.'], 422);
        }
        $mime = (string) $file->getMimeType();
        if (str_contains($mime, 'html') || str_contains($mime, 'svg') || str_contains($mime, 'javascript')) {
            return response()->json(['message' => 'This file type cannot be attached.'], 422);
        }

        $name = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('knowledge/'.$article->id, $name, 'local');
        $attachment = $article->attachments()->create([
            'uploaded_by' => $user->id,
            'filename' => $this->safeName($file->getClientOriginalName(), $extension),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
        ]);

        return response()->json(['data' => [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'mime' => $attachment->mime,
            'size' => $attachment->size,
        ]], 201);
    }

    public function download(Request $request, KnowledgeAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        $article = $attachment->article;
        abort_unless($user instanceof User && $article !== null && ArticleAccess::canRead($user, $article), 404);
        abort_unless($this->safePath($attachment->path), 404);

        $downloadName = $this->safeName($attachment->filename, pathinfo($attachment->path, PATHINFO_EXTENSION));

        return Storage::disk('local')->download($attachment->path, $downloadName, [
            'Content-Type' => $attachment->mime,
        ]);
    }

    public function destroy(Request $request, KnowledgeAttachment $attachment): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('manage knowledge attachments'), 403);
        if ($this->safePath($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }
        $attachment->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function safePath(string $path): bool
    {
        return str_starts_with($path, 'knowledge/') && ! str_contains($path, '..');
    }

    private function safeName(string $filename, string $extension): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $clean = trim((string) preg_replace('/[^A-Za-z0-9._ -]+/', '', $base));
        if ($clean === '') {
            $clean = 'attachment';
        }

        return $clean.'.'.preg_replace('/[^a-z0-9]+/', '', strtolower($extension));
    }
}
