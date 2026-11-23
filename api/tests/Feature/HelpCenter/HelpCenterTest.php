<?php

namespace Tests\Feature\HelpCenter;

use Domain\Auth\Models\User;
use Domain\HelpCenter\Models\HelpArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_requests_cannot_read_search_or_open_screenshots(): void
    {
        $this->getJson('/api/v1/help')->assertUnauthorized();
        $this->getJson('/api/v1/help/contexts')->assertUnauthorized();
        $this->getJson('/api/v1/help/search?q=import')->assertUnauthorized();
        $this->getJson('/api/v1/help/context?key=employees.import')->assertUnauthorized();
        $this->getJson('/api/v1/help/articles/slug/import')->assertUnauthorized();
        $this->getJson('/api/v1/help/screenshots/1')->assertUnauthorized();
    }

    public function test_readers_only_see_guides_they_are_allowed_to_read(): void
    {
        $editor = $this->editor();
        $published = $this->article($editor, 'Collega zoeken', 'published');
        $this->article($editor, 'Conceptgids', 'draft');
        $private = $this->article($editor, 'Interne notitie', 'published', 'private');
        $restricted = $this->article($editor, 'Importeren', 'published', 'authenticated', 'import employees', 'employees.import');

        $this->actingAsColleague();
        $this->getJson('/api/v1/help/search?q=notitie')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/help/search?q=Importeren')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/help/search?q=Collega')->assertOk()->assertJsonPath('data.0.slug', $published->slug);
        $this->getJson('/api/v1/help/articles/slug/'.$private->slug)->assertNotFound();
        $this->getJson('/api/v1/help/articles/slug/'.$restricted->slug)->assertNotFound();
        $this->getJson('/api/v1/help/context?key=employees.import')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_public_visibility_cannot_bypass_authentication_and_publish_is_permissioned(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor);

        $created = $this->postJson('/api/v1/help/articles', [
            'title' => 'Aanmelden',
            'visibility' => 'public',
            'context_key' => 'auth.sign-in',
            'content' => $this->document('Vul je e-mail en wachtwoord in.'),
        ])->assertCreated();

        $this->assertSame('authenticated', $created->json('data.visibility'));
        $this->assertFalse(config('help.public_access_enabled'));

        $id = $created->json('data.id');
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/v1/help/articles/'.$id.'/publish', [
            'version' => 1,
            'content' => $this->document('Vul je e-mail en wachtwoord in.'),
        ])->assertForbidden();

        $this->actingAs($editor);
        $this->postJson('/api/v1/help/articles/'.$id.'/publish', [
            'version' => 1,
            'title' => 'Aanmelden',
            'content' => $this->document('Vul je e-mail en wachtwoord in.'),
        ])->assertOk()->assertJsonPath('data.status', 'published');

        $this->putJson('/api/v1/help/articles/'.$id, [
            'version' => 1,
            'title' => 'Te laat',
            'content' => $this->document('Andere tekst.'),
        ])->assertStatus(409);
    }

    public function test_unsafe_links_are_rejected_and_restore_keeps_history(): void
    {
        $editor = $this->editor();
        $article = $this->article($editor, 'Organogram', 'published');
        $article->revisions()->create([
            'number' => 1,
            'title' => $article->title,
            'summary' => null,
            'content' => $article->content,
            'editor_id' => $editor->id,
            'note' => null,
        ]);
        $this->actingAs($editor);

        $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 1,
            'title' => 'Organogram',
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Open',
                        'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]],
                    ]],
                ]],
            ],
        ])->assertStatus(422);

        $this->postJson('/api/v1/help/articles/'.$article->id.'/revisions/1/restore', [
            'version' => 1,
        ])->assertOk()->assertJsonPath('data.status', 'published')->assertJsonPath('data.version', 2);

        $this->assertSame(2, $article->fresh()->revisions()->count());
    }

    public function test_instruction_blocks_drop_preview_urls_and_feedback_is_one_vote(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $editor = $this->editor();
        $article = $this->article($editor, 'Importeren uitleg', 'published');
        $this->actingAs($editor);

        $saved = $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 1,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Stappen']]],
                    ['type' => 'orderedList', 'content' => [
                        ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Kies het bestand.']]]]],
                    ]],
                    ['type' => 'stepList', 'content' => [
                        ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Open het scherm.']]]]],
                    ]],
                    ['type' => 'callout', 'attrs' => ['kind' => 'warning'], 'content' => [
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Controleer de kolommen.']]],
                    ]],
                    ['type' => 'blockquote', 'content' => [
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Een bestaande notitie.']]],
                    ]],
                    ['type' => 'docImage', 'attrs' => [
                        'id' => 4,
                        'alt' => 'Importdialoog',
                        'caption' => 'Stap 1',
                        'size' => 'md',
                        'src' => 'blob:secret',
                    ]],
                    ['type' => 'paragraph', 'content' => [[
                        'type' => 'text',
                        'text' => "Collega's",
                        'marks' => [['type' => 'link', 'attrs' => ['href' => '/admin/employees']]],
                    ]]],
                ],
            ],
        ])->assertOk();

        $image = collect($saved->json('data.content.content'))->firstWhere('type', 'docImage');
        $this->assertSame(4, $image['attrs']['id']);
        $this->assertArrayNotHasKey('src', $image['attrs']);
        $this->assertSame('warning', collect($saved->json('data.content.content'))->firstWhere('type', 'callout')['attrs']['kind']);

        $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 2,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'docImage',
                    'attrs' => ['id' => 0, 'alt' => 'Ontbreekt', 'src' => 'javascript:alert(1)'],
                ]],
            ],
        ])->assertStatus(422);

        $uploaded = $this->post('/api/v1/help/articles/'.$article->id.'/screenshots', [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('scherm.png'),
            'alt_text' => 'Importdialoog',
            'caption' => 'Stap 1',
        ])->assertCreated();
        $this->assertArrayNotHasKey('path', $uploaded->json('data'));
        $this->post('/api/v1/help/articles/'.$article->id.'/screenshots', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('script.html', 20, 'text/html'),
            'alt_text' => 'Pagina',
        ])->assertStatus(422);

        $shot = $uploaded->json('data.id');
        $this->get('/api/v1/help/screenshots/'.$shot)->assertOk();

        $this->postJson('/api/v1/help/articles/'.$article->id.'/feedback', ['helpful' => true])->assertOk();
        $this->postJson('/api/v1/help/articles/'.$article->id.'/feedback', ['helpful' => false])->assertOk();
        $this->getJson('/api/v1/help/contexts')->assertOk()->assertJsonFragment(['key' => 'employees.directory', 'feature_path' => '/admin/employees']);

        $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 2,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'heading', 'attrs' => ['level' => 4], 'content' => [['type' => 'text', 'text' => 'Details']]],
                    ['type' => 'docStep', 'content' => [
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Open collega\'s']]],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Gebruik de zijbalk.']]],
                    ]],
                    ['type' => 'horizontalRule'],
                    ['type' => 'featureLink', 'attrs' => [
                        'label' => 'Open collega\'s',
                        'description' => 'Ga naar de lijst.',
                        'contextKey' => 'employees.directory',
                        'href' => 'javascript:alert(1)',
                    ]],
                ],
            ],
        ])->assertStatus(422);

        $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 2,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'widget',
                    'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Onbekend']]]],
                ]],
            ],
        ])->assertStatus(422);
        $this->assertSame(2, $article->fresh()->version);

        $linked = $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 2,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'docStep', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Open collega\'s']]]]],
                    ['type' => 'horizontalRule'],
                    ['type' => 'featureLink', 'attrs' => [
                        'label' => 'Open collega\'s',
                        'description' => 'Ga naar de lijst.',
                        'contextKey' => 'employees.directory',
                    ]],
                ],
            ],
        ])->assertOk();
        $link = collect($linked->json('data.content.content'))->firstWhere('type', 'featureLink');
        $this->assertSame('employees.directory', $link['attrs']['contextKey']);
        $this->assertArrayNotHasKey('href', $link['attrs']);
        $this->assertSame('horizontalRule', collect($linked->json('data.content.content'))->firstWhere('type', 'horizontalRule')['type']);

        $this->putJson('/api/v1/help/articles/'.$article->id, [
            'version' => 3,
            'title' => 'Importeren uitleg',
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'featureLink',
                    'attrs' => ['label' => 'Ergens', 'contextKey' => 'not.registered'],
                ]],
            ],
        ])->assertStatus(422);

        $this->assertSame(1, \Domain\HelpCenter\Models\HelpArticleFeedback::query()->where('help_article_id', $article->id)->count());
        $this->getJson('/api/v1/help/articles/slug/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.feedback.mine', false)
            ->assertJsonPath('data.feedback.not_helpful', 1)
            ->assertJsonPath('data.feedback.helpful', 0);
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        foreach (['create help articles', 'update help articles', 'publish help articles', 'view help revisions', 'restore help revisions'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function article(User $author, string $title, string $status, string $visibility = 'authenticated', ?string $audience = null, ?string $context = null): HelpArticle
    {
        $prepared = \Domain\HelpCenter\Support\HelpContent::prepare($this->document($title));

        return HelpArticle::query()->create([
            'author_id' => $author->id,
            'editor_id' => $author->id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'content' => $prepared['document'],
            'search_text' => $prepared['text'],
            'status' => $status,
            'visibility' => $visibility,
            'context_key' => $context,
            'audience_permission' => $audience,
            'version' => 1,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(string $text): array
    {
        return [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $text]],
            ]],
        ];
    }
}
