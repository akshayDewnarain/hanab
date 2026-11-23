<?php

namespace Application\API\HelpCenter\Controllers;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpAttachment;
use Domain\HelpCenter\Support\HelpAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HelpMediaController
{
    public function store(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        abort_unless(config('help.attachments_enabled') === true, 404);
        abort_unless($user instanceof User && $user->can('update help articles'), 403);

        $extensions = config('help.image_extensions');
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('help.max_attachment_kilobytes')],
            'alt_text' => ['required', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:180'],
        ]);
        $file = $data['file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        if (! is_array($extensions) || ! in_array($extension, $extensions, true) || ! str_starts_with($mime, 'image/')) {
            return response()->json(['message' => 'Only PNG, JPG and WebP screenshots can be added.'], 422);
        }
        if (str_contains($mime, 'svg') || str_contains($mime, 'html') || str_contains($mime, 'javascript')) {
            return response()->json(['message' => 'This image type cannot be added.'], 422);
        }

        $name = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('help/'.$article->id, $name, 'local');
        $attachment = $article->attachments()->create([
            'uploaded_by' => $user->id,
            'filename' => $this->safeName($file->getClientOriginalName(), $extension),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'caption' => $data['caption'] ?? null,
            'alt_text' => trim($data['alt_text']),
        ]);

        return response()->json(['data' => [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'mime' => $attachment->mime,
            'size' => $attachment->size,
            'caption' => $attachment->caption,
            'alt_text' => $attachment->alt_text,
        ]], 201);
    }

    public function download(Request $request, HelpAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        $article = $attachment->article;
        abort_unless($user instanceof User && $article !== null && HelpAccess::canRead($user, $article), 404);
        abort_unless($this->safePath($attachment->path), 404);

        return Storage::disk('local')->response($attachment->path, $this->safeName($attachment->filename, pathinfo($attachment->path, PATHINFO_EXTENSION)), [
            'Content-Type' => $attachment->mime,
            'Content-Disposition' => 'inline; filename="'.$this->safeName($attachment->filename, pathinfo($attachment->path, PATHINFO_EXTENSION)).'"',
        ]);
    }

    public function destroy(Request $request, HelpAttachment $attachment): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('update help articles'), 403);
        if ($this->safePath($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }
        $attachment->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function safePath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, 'help/') && ! str_contains($path, '..');
    }

    private function safeName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $clean = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $base), '-');
        $clean = $clean === '' ? 'screenshot' : mb_substr($clean, 0, 80);
        $ext = strtolower(preg_replace('/[^a-z0-9]+/', '', $extension) ?? '');

        return $ext === '' ? $clean : $clean.'.'.$ext;
    }
}
