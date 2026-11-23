<?php

namespace Tests\Feature\Knowledge;

use Domain\Auth\Models\User;
use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_requests_cannot_read_or_search_articles(): void
    {
        $this->getJson('/api/v1/knowledge')->assertUnauthorized();
        $this->getJson('/api/v1/knowledge/search?q=safety')->assertUnauthorized();
        $this->getJson('/api/v1/knowledge/articles/slug/safety')->assertUnauthorized();
        $this->getJson('/api/v1/knowledge/attachments/1')->assertUnauthorized();
    }

    public function test_readers_only_see_published_authenticated_articles(): void
    {
        $editor = $this->editor();
        $published = $this->article($editor, 'Safety briefing', 'published');
        $this->article($editor, 'Hidden draft', 'draft');
        $private = $this->article($editor, 'Private note', 'published', 'private');
        $archived = $this->article($editor, 'Old note', 'archived');

        $this->actingAsColleague();
        $this->getJson('/api/v1/knowledge/search?q=note')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/knowledge/search?q=Safety')->assertOk()->assertJsonPath('data.0.slug', $published->slug);
        $this->getJson('/api/v1/knowledge/articles/slug/'.$private->slug)->assertNotFound();
        $this->getJson('/api/v1/knowledge/articles/slug/'.$archived->slug)->assertNotFound();
        $this->getJson('/api/v1/knowledge/articles/slug/'.$published->slug)
            ->assertOk()
            ->assertJsonPath('data.title', 'Safety briefing');
    }

    public function test_public_visibility_stays_behind_authentication(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);
        $created = $this->postJson('/api/v1/knowledge/articles', $this->payload('Public looking title', 'public'))
            ->assertCreated()
            ->json('data');

        $this->assertSame('authenticated', $created['visibility']);
        $this->assertFalse(config('knowledge.public_access_enabled'));
    }

    public function test_publishing_revisions_conflicts_and_unsafe_content(): void
    {
        $author = $this->editor();
        $this->actingAs($author);
        $draft = $this->postJson('/api/v1/knowledge/articles', $this->payload('Pump procedure'))->assertCreated()->json('data');

        $this->actingAs(User::factory()->create());
        $this->postJson('/api/v1/knowledge/articles/'.$draft['id'].'/publish', [
            ...$this->payload('Pump procedure'),
            'version' => $draft['version'],
        ])->assertForbidden();

        $this->actingAs($author);
        $published = $this->postJson('/api/v1/knowledge/articles/'.$draft['id'].'/publish', [
            ...$this->payload('Pump procedure revised'),
            'version' => $draft['version'],
            'note' => 'First publish',
        ])->assertOk()->json('data');
        $this->assertSame('published', $published['status']);

        $this->putJson('/api/v1/knowledge/articles/'.$draft['id'], [
            ...$this->payload('Stale title'),
            'version' => $draft['version'],
        ])->assertStatus(409);

        $this->putJson('/api/v1/knowledge/articles/'.$draft['id'], [
            ...$this->payload('javascript:alert(1)', 'authenticated', 'javascript:alert(1)'),
            'version' => $published['version'],
        ])->assertStatus(422);

        $restored = $this->postJson('/api/v1/knowledge/articles/'.$draft['id'].'/revisions/1/restore', [
            'version' => $published['version'],
        ])->assertOk()->json('data');
        $this->assertSame('published', $restored['status']);
        $this->assertSame(3, KnowledgeArticle::query()->find($draft['id'])->revisions()->count());
    }

    public function test_attachments_follow_the_article_policy(): void
    {
        Storage::fake('local');
        $editor = $this->editor();
        $article = $this->article($editor, 'Manual', 'published');
        $this->actingAs($editor);
        $uploaded = $this->postJson('/api/v1/knowledge/articles/'.$article->id.'/attachments', [
            'file' => UploadedFile::fake()->create('guide.txt', 4, 'text/plain'),
        ])->assertCreated()->json('data');

        $this->actingAsColleague();
        $this->get('/api/v1/knowledge/attachments/'.$uploaded['id'])->assertOk();

        $draft = $this->article($editor, 'Secret file', 'draft');
        $this->actingAs($editor);
        $secret = $this->postJson('/api/v1/knowledge/articles/'.$draft->id.'/attachments', [
            'file' => UploadedFile::fake()->create('secret.txt', 2, 'text/plain'),
        ])->assertCreated()->json('data');
        $this->actingAsColleague();
        $this->get('/api/v1/knowledge/attachments/'.$secret['id'])->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $title, string $visibility = 'authenticated', ?string $link = null): array
    {
        $text = ['type' => 'text', 'text' => $title];
        if ($link !== null) {
            $text['marks'] = [['type' => 'link', 'attrs' => ['href' => $link]]];
        }

        return [
            'title' => $title,
            'summary' => $title,
            'visibility' => $visibility,
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Overview']]],
                    ['type' => 'paragraph', 'content' => [$text]],
                ],
            ],
        ];
    }

    private function article(User $author, string $title, string $status, string $visibility = 'authenticated'): KnowledgeArticle
    {
        $this->actingAs($author);
        $created = $this->postJson('/api/v1/knowledge/articles', $this->payload($title, $visibility))->assertCreated()->json('data');
        if ($status === 'published') {
            $created = $this->postJson('/api/v1/knowledge/articles/'.$created['id'].'/publish', [
                ...$this->payload($title, $visibility),
                'version' => $created['version'],
            ])->assertOk()->json('data');
        }
        if ($status === 'archived') {
            $created = $this->postJson('/api/v1/knowledge/articles/'.$created['id'].'/publish', [
                ...$this->payload($title, $visibility),
                'version' => $created['version'],
            ])->assertOk()->json('data');
            $this->postJson('/api/v1/knowledge/articles/'.$created['id'].'/archive', [
                'version' => $created['version'],
            ])->assertOk();
        }

        return KnowledgeArticle::query()->findOrFail($created['id']);
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        foreach ([
            'create knowledge articles',
            'update knowledge articles',
            'publish knowledge articles',
            'manage knowledge attachments',
            'view knowledge revisions',
            'restore knowledge revisions',
        ] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
