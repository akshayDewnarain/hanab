<?php

namespace Tests\Feature\HelpCenter;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Domain\HelpCenter\Models\HelpArticleFeedback;
use Domain\HelpCenter\Models\HelpCategory;
use Domain\HelpCenter\Support\HelpContent;
use Domain\HelpCenter\Support\HelpLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelpLibraryTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->root = storage_path('framework/testing/help-library-'.uniqid());
        File::ensureDirectoryExists($this->root);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_export_and_import_restore_articles_and_screenshots_after_a_wipe(): void
    {
        $author = User::factory()->create();
        $parent = HelpCategory::query()->create([
            'name' => "Collega's",
            'slug' => 'collegas',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        HelpCategory::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Zoeken',
            'slug' => 'zoeken',
            'description' => 'Zoeken in de lijst.',
            'icon' => 'material-symbols:search-rounded',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $article = $this->article($author, 'Collega zoeken', 'Zoek een collega op naam.');
        Storage::disk('local')->put('help/'.$article->id.'/shot.png', $png);
        $attachment = $article->attachments()->create([
            'uploaded_by' => $author->id,
            'filename' => 'directory.png',
            'path' => 'help/'.$article->id.'/shot.png',
            'mime' => 'image/png',
            'size' => strlen($png),
            'alt_text' => 'De collegalijst',
            'caption' => 'Zoeken staat bovenaan.',
        ]);
        $prepared = HelpContent::prepare([
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Zoek een collega op naam.']]],
                ['type' => 'docImage', 'attrs' => [
                    'id' => $attachment->id,
                    'alt' => 'De collegalijst',
                    'caption' => 'Zoeken staat bovenaan.',
                    'size' => 'lg',
                    'align' => 'center',
                    'src' => 'http://localhost/preview',
                ]],
            ],
        ]);
        $article->update([
            'category_id' => HelpCategory::query()->where('slug', 'zoeken')->value('id'),
            'content' => $prepared['document'],
            'search_text' => $prepared['text'],
            'context_key' => 'employees.directory',
            'feature_path' => '/admin/employees',
            'is_getting_started' => true,
            'status' => HelpArticle::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        HelpArticleFeedback::query()->create([
            'help_article_id' => $article->id,
            'user_id' => $author->id,
            'helpful' => true,
        ]);

        $library = new HelpLibrary;
        $exported = $library->export($this->root);

        $this->assertSame(['articles' => 1, 'images' => 1], $exported);
        $stored = json_decode(File::get($this->root.'/articles/collega-zoeken.json'), true);
        $this->assertSame('directory.png', $stored['content']['content'][1]['attrs']['id']);
        $this->assertArrayNotHasKey('src', $stored['content']['content'][1]['attrs']);
        $this->assertSame('zoeken', $stored['category']);
        $this->assertSame(hash('sha256', $png), $stored['images'][0]['sha256']);
        $this->assertSame($png, File::get($this->root.'/images/collega-zoeken/directory.png'));

        HelpArticle::query()->delete();
        HelpCategory::query()->delete();
        Storage::disk('local')->deleteDirectory('help');

        $imported = $library->import($this->root);
        $restored = HelpArticle::query()->where('slug', 'collega-zoeken')->firstOrFail();
        $shot = $restored->attachments()->firstOrFail();
        $document = json_decode($restored->content, true);

        $this->assertSame(['articles' => 1, 'images' => 1, 'removed' => 0], $imported);
        $this->assertSame('Zoeken', $restored->category->name);
        $this->assertSame('collegas', $restored->category->parent->slug);
        $this->assertSame($shot->id, $document['content'][1]['attrs']['id']);
        $this->assertSame('lg', $document['content'][1]['attrs']['size']);
        $this->assertSame($png, Storage::disk('local')->get($shot->path));
        $this->assertSame(0644, fileperms(Storage::disk('local')->path($shot->path)) & 0777);
        $this->assertSame(0755, fileperms(dirname(Storage::disk('local')->path($shot->path))) & 0777);
        $this->assertSame(1, $restored->version);
        $this->assertSame(1, $restored->revisions()->count());
        $this->assertSame(0, HelpArticleFeedback::query()->count());

        $this->actingAs($author);
        $this->get('/api/v1/help/screenshots/'.$shot->id)->assertOk();

        $again = $library->import($this->root);
        $this->assertSame(1, HelpArticle::query()->count());
        $this->assertSame(1, $restored->fresh()->version);
        $this->assertSame($shot->id, $restored->fresh()->attachments()->value('id'));
        $this->assertSame(0, $again['removed']);
    }

    public function test_import_removes_articles_dropped_from_the_library_and_refuses_an_empty_one(): void
    {
        $author = User::factory()->create();
        $keep = $this->article($author, 'Bewaren', 'Deze gids blijft.');
        $drop = $this->article($author, 'Weghalen', 'Deze gids verdwijnt.');
        $library = new HelpLibrary;
        $library->export($this->root);

        File::delete($this->root.'/articles/'.$drop->slug.'.json');
        $result = $library->import($this->root);

        $this->assertSame(1, $result['removed']);
        $this->assertNotNull(HelpArticle::query()->find($keep->id));
        $this->assertNull(HelpArticle::query()->find($drop->id));

        File::deleteDirectory($this->root.'/articles');
        File::put($this->root.'/categories.json', "[]\n");

        $this->expectException(\InvalidArgumentException::class);
        $library->import($this->root);
    }

    private function article(User $author, string $title, string $text): HelpArticle
    {
        $prepared = HelpContent::prepare([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $text]],
            ]],
        ]);

        return HelpArticle::query()->create([
            'author_id' => $author->id,
            'editor_id' => $author->id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'content' => $prepared['document'],
            'search_text' => $prepared['text'],
            'status' => HelpArticle::STATUS_PUBLISHED,
            'visibility' => HelpArticle::VISIBILITY_AUTHENTICATED,
            'version' => 1,
            'published_at' => now(),
        ]);
    }
}
