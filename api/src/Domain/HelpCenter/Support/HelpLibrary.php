<?php

namespace Domain\HelpCenter\Support;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpAttachment;
use Domain\HelpCenter\Models\HelpCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * The help library is the copy of the help center that travels with the code.
 *
 * Export writes categories, articles, and screenshots into a folder. Import
 * reads that folder and rebuilds the same help center after a database wipe.
 * Screenshot links in the files use filenames. The database still stores
 * attachment ids, and those ids are assigned again on import.
 */
final class HelpLibrary
{
    /**
     * @return array{articles: int, images: int}
     */
    public function export(string $root): array
    {
        $root = $this->root($root);
        File::ensureDirectoryExists($root.'/articles');
        File::ensureDirectoryExists($root.'/images');

        $categories = HelpCategory::query()->with('parent')->orderBy('sort_order')->orderBy('slug')->get();
        $this->writeJson($root.'/categories.json', $categories->map(fn (HelpCategory $category) => [
            'slug' => $this->slug($category->slug),
            'name' => $category->name,
            'description' => $category->description,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'parent' => $category->parent?->slug,
        ])->values()->all());

        $articles = HelpArticle::query()->with(['category', 'attachments'])->orderBy('slug')->get();
        $slugs = [];
        $images = 0;
        foreach ($articles as $article) {
            $payload = $this->payload($article, $root);
            $slugs[] = $payload['slug'];
            $images += count($payload['images']);
            $this->writeJson($root.'/articles/'.$payload['slug'].'.json', $payload);
        }

        $this->prune($root.'/articles', $slugs, false);
        $this->prune($root.'/images', $slugs, true);

        return ['articles' => count($slugs), 'images' => $images];
    }

    /**
     * @return array{articles: int, images: int, removed: int}
     */
    public function import(string $root, bool $replace = false): array
    {
        $root = $this->root($root);
        $categories = $this->readJson($root.'/categories.json');
        if (! array_is_list($categories)) {
            throw new InvalidArgumentException('categories.json must be a list of categories.');
        }

        $articleFiles = File::isDirectory($root.'/articles') ? File::files($root.'/articles') : [];
        $payloads = [];
        foreach ($articleFiles as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $payload = $this->readJson($file->getPathname());
            $slug = $this->slug((string) ($payload['slug'] ?? $file->getFilenameWithoutExtension()));
            if ($slug !== $file->getFilenameWithoutExtension()) {
                throw new InvalidArgumentException("The file [{$file->getFilename()}] does not match its article slug.");
            }
            $payloads[$slug] = $payload;
        }
        ksort($payloads);

        if ($payloads === [] && ! $replace) {
            throw new InvalidArgumentException('The help library has no articles. Export the help center first, or pass --replace to clear it.');
        }

        $created = [];
        $obsolete = [];
        try {
            $result = DB::transaction(function () use ($root, $categories, $payloads, &$created, &$obsolete) {
                return $this->load($root, $categories, $payloads, $created, $obsolete);
            });
        } catch (Throwable $exception) {
            $this->deleteStored($created);
            throw $exception;
        }
        $this->deleteStored($obsolete);

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $categories
     * @param  array<string, array<string, mixed>>  $payloads
     * @param  list<string>  $created
     * @param  list<string>  $obsolete
     * @return array{articles: int, images: int, removed: int}
     */
    private function load(string $root, array $categories, array $payloads, array &$created, array &$obsolete): array
    {
        $categoryIds = $this->loadCategories($categories);
        $ownerId = User::query()->orderBy('id')->value('id');
        $images = 0;

        foreach ($payloads as $slug => $payload) {
            $images += $this->loadArticle($root, $slug, $payload, $categoryIds, $ownerId === null ? null : (int) $ownerId, $created, $obsolete);
        }

        $removed = 0;
        $dropping = array_keys($payloads) === []
            ? HelpArticle::query()->with('attachments')->get()
            : HelpArticle::query()->whereNotIn('slug', array_keys($payloads))->with('attachments')->get();
        foreach ($dropping as $article) {
            foreach ($article->attachments as $attachment) {
                if ($this->storedPath($attachment->path)) {
                    $obsolete[] = $attachment->path;
                }
            }
            $article->delete();
            $removed++;
        }

        $categoryQuery = array_keys($categoryIds) === []
            ? HelpCategory::query()
            : HelpCategory::query()->whereNotIn('slug', array_keys($categoryIds));
        $categoryIdsToRemove = $categoryQuery->pluck('id');
        HelpCategory::query()->whereIn('parent_id', $categoryIdsToRemove)->update(['parent_id' => null]);
        HelpCategory::query()->whereIn('id', $categoryIdsToRemove)->delete();

        return ['articles' => count($payloads), 'images' => $images, 'removed' => $removed];
    }

    /**
     * @param  list<array<string, mixed>>  $categories
     * @return array<string, int>
     */
    private function loadCategories(array $categories): array
    {
        $ids = [];
        $parents = [];
        foreach ($categories as $category) {
            if (! is_array($category)) {
                throw new InvalidArgumentException('A help category in the library is not valid.');
            }
            $slug = $this->slug((string) ($category['slug'] ?? ''));
            $name = $this->text($category['name'] ?? null, 255, true, "The category [{$slug}] needs a name.");
            $model = HelpCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $this->text($category['description'] ?? null, 5000, false),
                'icon' => $this->text($category['icon'] ?? null, 80, false),
                'sort_order' => (int) ($category['sort_order'] ?? 0),
                'is_active' => (bool) ($category['is_active'] ?? true),
            ]);
            $ids[$slug] = $model->id;
            $parent = $category['parent'] ?? null;
            $parents[$slug] = is_string($parent) && trim($parent) !== '' ? $this->slug($parent) : null;
        }

        foreach ($parents as $slug => $parent) {
            if ($parent !== null && ! array_key_exists($parent, $ids)) {
                throw new InvalidArgumentException("The category [{$slug}] points at [{$parent}], which is not in the library.");
            }
            HelpCategory::query()->whereKey($ids[$slug])->update([
                'parent_id' => $parent === null ? null : $ids[$parent],
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $categoryIds
     * @param  list<string>  $created
     * @param  list<string>  $obsolete
     */
    private function loadArticle(string $root, string $slug, array $payload, array $categoryIds, ?int $ownerId, array &$created, array &$obsolete): int
    {
        $categorySlug = $payload['category'] ?? null;
        $categoryId = null;
        if (is_string($categorySlug) && trim($categorySlug) !== '') {
            $categorySlug = $this->slug($categorySlug);
            if (! array_key_exists($categorySlug, $categoryIds)) {
                throw new InvalidArgumentException("The article [{$slug}] uses the category [{$categorySlug}], which is not in the library.");
            }
            $categoryId = $categoryIds[$categorySlug];
        }

        $title = $this->text($payload['title'] ?? null, 255, true, "The article [{$slug}] needs a title.");
        $summary = $this->text($payload['summary'] ?? null, 500, false);
        $status = (string) ($payload['status'] ?? HelpArticle::STATUS_DRAFT);
        if (! in_array($status, [HelpArticle::STATUS_DRAFT, HelpArticle::STATUS_PUBLISHED, HelpArticle::STATUS_ARCHIVED], true)) {
            throw new InvalidArgumentException("The article [{$slug}] has a status the help center does not use.");
        }
        $visibility = HelpAccess::normalizeVisibility((string) ($payload['visibility'] ?? HelpArticle::VISIBILITY_AUTHENTICATED));
        if (! in_array($visibility, [HelpArticle::VISIBILITY_PRIVATE, HelpArticle::VISIBILITY_AUTHENTICATED, HelpArticle::VISIBILITY_PUBLIC], true)) {
            throw new InvalidArgumentException("The article [{$slug}] has a visibility the help center does not use.");
        }

        $article = HelpArticle::query()->firstOrNew(['slug' => $slug]);
        $fresh = ! $article->exists;
        if ($fresh) {
            $article->fill([
                'author_id' => $ownerId,
                'title' => $title,
                'content' => '{"type":"doc","content":[]}',
                'version' => 1,
            ]);
            $article->save();
        }

        $images = is_array($payload['images'] ?? null) ? $payload['images'] : [];
        $fileIds = $this->syncImages($root, $article, $images, $ownerId, $created, $obsolete);
        $document = $this->assignImageIds(is_array($payload['content'] ?? null) ? $payload['content'] : [], $fileIds, $slug);
        $prepared = HelpContent::prepare($document);
        if ($prepared === null) {
            throw new InvalidArgumentException("The article [{$slug}] has content the help center cannot store.");
        }

        $previous = $article->content;
        $article->fill([
            'category_id' => $categoryId,
            'editor_id' => $ownerId,
            'title' => $title,
            'summary' => $summary,
            'content' => $prepared['document'],
            'search_text' => $prepared['text'],
            'keywords' => $this->text($payload['keywords'] ?? null, 240, false),
            'status' => $status,
            'visibility' => $visibility,
            'context_key' => $this->contextKey($payload['context_key'] ?? null, $slug),
            'audience_permission' => $this->text($payload['audience_permission'] ?? null, 80, false),
            'feature_path' => $this->featurePath($payload['feature_path'] ?? null, $slug),
            'app_version' => $this->text($payload['app_version'] ?? null, 40, false),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_getting_started' => (bool) ($payload['is_getting_started'] ?? false),
            'review_due_on' => $this->date($payload['review_due_on'] ?? null, $slug),
        ]);
        if ($status === HelpArticle::STATUS_PUBLISHED) {
            $article->published_at = $article->published_at ?? now();
        } else {
            $article->published_at = null;
        }

        $changed = $fresh || $article->isDirty() || $previous !== $prepared['document'];
        if ($changed && ! $fresh) {
            $article->version = (int) $article->version + 1;
        }
        $article->save();

        if ($changed && config('help.revisions_enabled') !== false) {
            $article->revisions()->create([
                'number' => ((int) $article->revisions()->max('number')) + 1,
                'title' => $article->title,
                'summary' => $article->summary,
                'content' => $article->content,
                'editor_id' => $ownerId,
                'note' => 'Imported from the help library',
            ]);
        }

        return count($images);
    }

    /**
     * @param  list<mixed>  $images
     * @param  list<string>  $created
     * @param  list<string>  $obsolete
     * @return array<string, int>
     */
    private function syncImages(string $root, HelpArticle $article, array $images, ?int $ownerId, array &$created, array &$obsolete): array
    {
        $wanted = [];
        foreach ($images as $image) {
            if (! is_array($image)) {
                throw new InvalidArgumentException("A screenshot on [{$article->slug}] is not valid.");
            }
            $file = $this->filename((string) ($image['file'] ?? ''));
            $bytes = File::get($this->imageFile($root, $article->slug, $file));
            $expected = strtolower((string) ($image['sha256'] ?? ''));
            if ($expected !== '' && ! hash_equals($expected, hash('sha256', $bytes))) {
                throw new InvalidArgumentException("The screenshot [{$file}] on [{$article->slug}] does not match the library.");
            }
            $wanted[$file] = [
                'bytes' => $bytes,
                'mime' => $this->mime((string) ($image['mime'] ?? ''), $file, $article->slug),
                'alt_text' => $this->text($image['alt_text'] ?? null, 180, true, "The screenshot [{$file}] needs alt text."),
                'caption' => $this->text($image['caption'] ?? null, 180, false),
                'sha256' => hash('sha256', $bytes),
            ];
        }

        $article->load('attachments');
        $reusable = [];
        foreach ($article->attachments as $attachment) {
            $name = $attachment->filename;
            $stored = $this->storedPath($attachment->path) ? Storage::disk('local')->get($attachment->path) : null;
            if (is_string($stored) && isset($wanted[$name]) && hash_equals($wanted[$name]['sha256'], hash('sha256', $stored)) && $attachment->alt_text === $wanted[$name]['alt_text'] && $attachment->caption === $wanted[$name]['caption']) {
                $reusable[$name] = $attachment->id;
                continue;
            }
            if ($this->storedPath($attachment->path)) {
                $obsolete[] = $attachment->path;
            }
            $attachment->delete();
        }

        $ids = [];
        foreach ($wanted as $file => $image) {
            if (isset($reusable[$file])) {
                $ids[$file] = $reusable[$file];
                continue;
            }
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $path = 'help/'.$article->id.'/'.Str::uuid()->toString().'.'.$extension;
            Storage::disk('local')->put($path, $image['bytes']);
            $created[] = $path;
            $attachment = $article->attachments()->create([
                'uploaded_by' => $ownerId,
                'filename' => $file,
                'path' => $path,
                'mime' => $image['mime'],
                'size' => strlen($image['bytes']),
                'caption' => $image['caption'],
                'alt_text' => $image['alt_text'],
            ]);
            $ids[$file] = $attachment->id;
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(HelpArticle $article, string $root): array
    {
        $slug = $this->slug($article->slug);
        $used = [];
        $keys = [];
        $images = [];
        File::ensureDirectoryExists($root.'/images/'.$slug);

        foreach ($article->attachments as $attachment) {
            $file = $this->uniqueFilename($attachment->filename, (string) pathinfo($attachment->path, PATHINFO_EXTENSION), $used);
            if (! $this->storedPath($attachment->path) || ! Storage::disk('local')->exists($attachment->path)) {
                throw new InvalidArgumentException("The article [{$slug}] is missing the screenshot [{$attachment->filename}].");
            }
            $bytes = Storage::disk('local')->get($attachment->path);
            File::put($root.'/images/'.$slug.'/'.$file, $bytes);
            $keys[(string) $attachment->id] = $file;
            $images[] = [
                'file' => $file,
                'alt_text' => $attachment->alt_text,
                'caption' => $attachment->caption,
                'mime' => $attachment->mime,
                'sha256' => hash('sha256', $bytes),
            ];
        }

        $decoded = json_decode($article->content, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException("The article [{$slug}] has content the help center cannot store.");
        }

        $kept = array_column($images, 'file');
        foreach (File::files($root.'/images/'.$slug) as $existing) {
            if (! in_array($existing->getFilename(), $kept, true)) {
                File::delete($existing->getPathname());
            }
        }

        return [
            'slug' => $slug,
            'title' => $article->title,
            'summary' => $article->summary,
            'category' => $article->category?->slug,
            'keywords' => $article->keywords,
            'status' => $article->status,
            'visibility' => $article->visibility,
            'context_key' => $article->context_key,
            'audience_permission' => $article->audience_permission,
            'feature_path' => $article->feature_path,
            'app_version' => $article->app_version,
            'sort_order' => $article->sort_order,
            'is_getting_started' => $article->is_getting_started,
            'review_due_on' => $article->review_due_on?->toDateString(),
            'content' => $this->portableDocument($decoded, $keys, $slug),
            'images' => $images,
        ];
    }

    /**
     * @param  array<mixed>  $node
     * @param  array<string, string>  $keys
     * @return array<mixed>
     */
    private function portableDocument(array $node, array $keys, string $slug): array
    {
        if (($node['type'] ?? null) === 'docImage') {
            $id = (string) ($node['attrs']['id'] ?? '');
            if (! array_key_exists($id, $keys)) {
                throw new InvalidArgumentException("The article [{$slug}] refers to screenshot {$id}, which is not uploaded.");
            }
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $attrs['id'] = $keys[$id];
            unset($attrs['src']);
            $node['attrs'] = $attrs;

            return $node;
        }

        if (is_array($node['content'] ?? null)) {
            $node['content'] = array_map(
                fn ($child) => is_array($child) ? $this->portableDocument($child, $keys, $slug) : $child,
                $node['content'],
            );
        }

        return $node;
    }

    /**
     * @param  array<mixed>  $node
     * @param  array<string, int>  $fileIds
     * @return array<mixed>
     */
    private function assignImageIds(array $node, array $fileIds, string $slug): array
    {
        if (($node['type'] ?? null) === 'docImage') {
            $file = (string) ($node['attrs']['id'] ?? '');
            if (! array_key_exists($file, $fileIds)) {
                throw new InvalidArgumentException("The article [{$slug}] refers to [{$file}], which is not in its screenshots.");
            }
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $attrs['id'] = $fileIds[$file];
            unset($attrs['src']);
            $node['attrs'] = $attrs;

            return $node;
        }

        if (is_array($node['content'] ?? null)) {
            $node['content'] = array_map(
                fn ($child) => is_array($child) ? $this->assignImageIds($child, $fileIds, $slug) : $child,
                $node['content'],
            );
        }

        return $node;
    }

    /**
     * @param  list<string>  $slugs
     */
    private function prune(string $directory, array $slugs, bool $directories): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }
        if ($directories) {
            foreach (File::directories($directory) as $child) {
                if (! in_array(basename($child), $slugs, true)) {
                    File::deleteDirectory($child);
                }
            }

            return;
        }
        foreach (File::files($directory) as $file) {
            if ($file->getExtension() === 'json' && ! in_array($file->getFilenameWithoutExtension(), $slugs, true)) {
                File::delete($file->getPathname());
            }
        }
    }

    /**
     * @param  array<string, true>  $used
     */
    private function uniqueFilename(string $filename, string $extension, array &$used): string
    {
        try {
            $name = $this->filename($filename);
        } catch (InvalidArgumentException) {
            $name = $this->filename('screenshot.'.$this->extension($extension));
        }
        if (! isset($used[$name])) {
            $used[$name] = true;

            return $name;
        }
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = 2;
        do {
            $candidate = $base.'-'.$suffix.'.'.$ext;
            $suffix++;
        } while (isset($used[$candidate]));
        $used[$candidate] = true;

        return $candidate;
    }

    private function filename(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.[A-Za-z0-9]+\z/', $name) !== 1) {
            throw new InvalidArgumentException("The screenshot name [{$name}] cannot be stored as a file.");
        }
        $this->extension($name);

        return $name;
    }

    private function extension(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = config('help.image_extensions');
        if (! is_array($allowed) || ! in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException("The screenshot [{$name}] must be a PNG, JPG, or WebP file.");
        }

        return $extension;
    }

    private function mime(string $mime, string $file, string $slug): string
    {
        if (! str_starts_with($mime, 'image/') || str_contains($mime, 'svg') || str_contains($mime, 'html') || str_contains($mime, 'javascript')) {
            throw new InvalidArgumentException("The screenshot [{$file}] on [{$slug}] is not an allowed image.");
        }

        return $mime;
    }

    private function imageFile(string $root, string $slug, string $file): string
    {
        $directory = $root.'/images/'.$slug;
        $path = $directory.'/'.$file;
        $realDirectory = realpath($directory);
        $realPath = realpath($path);
        if ($realDirectory === false || $realPath === false || ! str_starts_with($realPath, $realDirectory.DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException("The screenshot [{$file}] on [{$slug}] is missing from the library.");
        }

        return $realPath;
    }

    private function contextKey(mixed $value, string $slug): ?string
    {
        $key = $this->text($value, 80, false);
        if ($key === null) {
            return null;
        }
        if (! HelpAccess::registeredContext($key)) {
            throw new InvalidArgumentException("The article [{$slug}] uses a help context that is not registered.");
        }

        return $key;
    }

    private function featurePath(mixed $value, string $slug): ?string
    {
        $path = $this->text($value, 180, false);
        if ($path === null) {
            return null;
        }
        if (! str_starts_with($path, '/') || str_contains($path, '://') || str_contains($path, '..')) {
            throw new InvalidArgumentException("The article [{$slug}] has a feature link that is not an internal path.");
        }

        return $path;
    }

    private function date(mixed $value, string $slug): ?string
    {
        $date = $this->text($value, 10, false);
        if ($date === null) {
            return null;
        }
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) !== 1) {
            throw new InvalidArgumentException("The article [{$slug}] has a review date the help center cannot store.");
        }

        return $date;
    }

    private function text(mixed $value, int $max, bool $required, string $message = ''): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            if ($required) {
                throw new InvalidArgumentException($message);
            }

            return null;
        }
        if (mb_strlen($text) > $max) {
            throw new InvalidArgumentException($message !== '' ? $message : 'A help library value is too long.');
        }

        return $text;
    }

    private function slug(string $slug): string
    {
        $slug = trim($slug);
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) !== 1) {
            throw new InvalidArgumentException("The help slug [{$slug}] cannot be stored as a file.");
        }

        return $slug;
    }

    private function storedPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, 'help/') && ! str_contains($path, '..');
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteStored(array $paths): void
    {
        foreach ($paths as $path) {
            if ($this->storedPath($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function root(string $root): string
    {
        $root = rtrim($root, '/');
        File::ensureDirectoryExists($root);

        return $root;
    }

    /**
     * @return array<mixed>
     */
    private function readJson(string $path): array
    {
        if (! File::isFile($path)) {
            throw new InvalidArgumentException('The help library is missing '.basename($path).'.');
        }
        $decoded = json_decode(File::get($path), true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException(basename($path).' is not valid help library data.');
        }

        return $decoded;
    }

    /**
     * @param  array<mixed>  $data
     */
    private function writeJson(string $path, array $data): void
    {
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }
}
