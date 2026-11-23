<?php

namespace Tests\Feature\Messages;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Employees\Actions\CreateEmployeeCertificateAction;
use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_an_imported_colleague_starts_as_a_draft_until_someone_publishes_it(): void
    {
        $admin = $this->actingAsSuperAdmin();
        EmployeeRole::factory()->create(['name' => 'Monteur', 'is_active' => true]);
        EmployeeLocation::factory()->create(['name' => 'Amsterdam', 'cluster_name' => 'West', 'is_active' => true]);

        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [[
                'employee_number' => 'EMP-1',
                'first_name' => 'Ada',
                'last_name' => 'Jansen',
                'email' => 'ada@hanab.test',
                'role' => 'Monteur',
                'location' => 'Amsterdam',
                'employment_type' => 'internal',
                'is_active' => 'true',
            ]],
        ])->assertOk();

        $this->assertSame(1, Message::query()->where('status', 'draft')->count());

        $reader = User::factory()->create();
        $this->actingAs($reader);
        $this->getJson('/api/v1/messages')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin);
        $draftId = Message::query()->value('id');
        $this->postJson('/api/v1/messages/'.$draftId.'/publish')->assertOk();

        $this->actingAs($reader);
        $this->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('data.0.subject_name', 'Ada Jansen')
            ->assertJsonPath('data.0.type', 'introduction');
    }

    public function test_a_birthday_message_hides_the_year_and_can_be_turned_off(): void
    {
        Carbon::setTestNow('2026-11-08');
        $reader = $this->actingAsSuperAdmin();

        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        Employee::factory()->create([
            'first_name' => 'Bo',
            'last_name' => 'Smit',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'date_of_birth' => '1990-11-08',
            'start_date' => '2020-11-08',
        ]);

        $inbox = $this->getJson('/api/v1/messages')->assertOk();
        $body = collect($inbox->json('data'))->firstWhere('type', 'birthday')['body'];
        $this->assertStringNotContainsString('1990', $body);
        $this->assertStringContainsString('Bo Smit', $body);

        $this->putJson('/api/v1/message-preferences', [
            'people' => true,
            'milestones' => false,
            'company' => false,
        ])->assertOk()->assertJsonPath('data.company', true);

        $this->postJson('/api/v1/messages', [
            'title' => 'Won the north lot',
            'body' => 'The team starts in January.',
        ])->assertCreated();

        $after = $this->getJson('/api/v1/messages')->assertOk()->json('data');
        $types = array_column($after, 'type');
        $this->assertNotContains('birthday', $types);
        $this->assertContains('contract', $types);

        $this->putJson('/api/v1/message-preferences', [
            'people' => true,
            'milestones' => true,
            'company' => true,
            'push_notifications' => false,
        ])->assertOk()->assertJsonPath('data.push_notifications', false);

        $withoutPush = $this->getJson('/api/v1/messages')->assertOk()->json('data');
        $this->assertNotContains('birthday', array_column($withoutPush, 'type'));
        $this->assertNotContains('anniversary', array_column($withoutPush, 'type'));
        $this->assertSame($reader->id, Message::query()->where('type', 'contract')->value('author_id'));
    }

    public function test_one_person_has_one_changeable_reaction(): void
    {
        $user = $this->actingAsSuperAdmin();
        $message = Message::query()->create([
            'type' => 'contract',
            'category' => 'company',
            'status' => 'published',
            'title' => 'Won',
            'body' => 'Done.',
            'published_at' => now(),
        ]);

        $this->putJson('/api/v1/messages/'.$message->id.'/reaction', ['emoji' => '👍'])->assertOk();
        $this->putJson('/api/v1/messages/'.$message->id.'/reaction', ['emoji' => '🎉'])->assertOk()
            ->assertJsonPath('data.reaction', '🎉');
        $this->assertSame(1, $message->reactions()->count());

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->putJson('/api/v1/messages/'.$message->id.'/reaction', ['emoji' => '👏'])->assertOk();
        $this->assertSame(2, $message->reactions()->count());

        $this->actingAs($user);
        $this->putJson('/api/v1/messages/'.$message->id.'/reaction', ['emoji' => null])->assertOk()
            ->assertJsonPath('data.reaction', null);
        $this->assertSame(1, $message->reactions()->count());
    }

    public function test_an_active_certificate_link_publishes_a_milestone(): void
    {
        $this->actingAsSuperAdmin();
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create([
            'first_name' => 'Cis',
            'last_name' => 'Vos',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
        ]);
        $certificate = Certificate::factory()->create(['name' => 'VCA', 'is_active' => true]);

        CreateEmployeeCertificateAction::make()->execute(EmployeeCertificateData::from([
            'employee_id' => $employee->id,
            'certificate_id' => $certificate->id,
        ]));

        $this->assertDatabaseHas('messages', [
            'type' => 'certificate',
            'detail' => 'VCA',
            'status' => 'published',
        ]);
    }

    public function test_a_guest_cannot_read_the_inbox_and_import_needs_the_permission(): void
    {
        $this->postJson('/api/v1/messages', [
            'title' => 'Won',
            'body' => 'Done.',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [['employee_number' => 'EMP-1']],
        ])->assertUnauthorized();

        $this->getJson('/api/v1/expertise-search?query=ketel')->assertUnauthorized();
        $this->getJson('/api/v1/transfers/employees/rows')->assertUnauthorized();

        $this->actingAsColleague();
        $this->getJson('/api/v1/expertise-search?query=ketel')->assertOk();
        $this->getJson('/api/v1/transfers/employees/rows')->assertForbidden();

        $permission = Permission::findOrCreate('import employees', 'web');
        $role = Role::findOrCreate('importer', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->postJson('/api/v1/transfers/skills/commit', [
            'rows' => [['name' => 'Blocked']],
        ])->assertForbidden();
    }

    public function test_company_news_keeps_a_validated_document_and_rejects_unsafe_content(): void
    {
        $this->actingAsSuperAdmin();
        $document = [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Read the update',
                    'marks' => [[
                        'type' => 'link',
                        'attrs' => [
                            'href' => 'https://hanab.example/news',
                            'target' => '_blank',
                            'rel' => null,
                            'class' => null,
                            'title' => null,
                        ],
                    ]],
                ]],
            ]],
        ];

        $this->postJson('/api/v1/messages', [
            'title' => 'Company update',
            'body' => json_encode($document),
            'status' => 'draft',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $stored = Message::query()->where('title', 'Company update')->value('body');
        $this->assertStringContainsString('https://hanab.example/news', (string) $stored);
        $this->assertStringNotContainsString('_blank', (string) $stored);
        $inbox = $this->getJson('/api/v1/messages')->assertOk();
        $this->assertNotContains('Company update', array_column($inbox->json('data'), 'title'));
        $this->assertContains('Company update', array_column($inbox->json('drafts'), 'title'));

        $this->postJson('/api/v1/messages', [
            'title' => 'Unsafe',
            'body' => json_encode([
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Click',
                        'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]],
                    ]],
                ]],
            ]),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/messages', [
            'title' => 'Script',
            'body' => json_encode(['type' => 'doc', 'content' => [['type' => 'script']]]),
        ])->assertUnprocessable();
    }

    public function test_a_draft_can_be_edited_and_only_a_draft_can_be_deleted(): void
    {
        $this->actingAsSuperAdmin();
        $created = $this->postJson('/api/v1/messages', [
            'title' => 'Draft title',
            'body' => 'Draft body',
            'status' => 'draft',
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->putJson('/api/v1/messages/'.$id, [
            'title' => 'Updated title',
            'body' => 'Updated body',
        ])->assertOk()->assertJsonPath('data.title', 'Updated title');

        $introduction = Message::query()->create([
            'type' => 'introduction',
            'category' => 'people',
            'status' => 'draft',
            'title' => 'New colleague',
            'body' => 'Ada joined.',
        ]);
        $this->putJson('/api/v1/messages/'.$introduction->id, [
            'title' => 'Changed',
            'body' => 'Changed body',
        ])->assertUnprocessable();

        $published = Message::query()->create([
            'type' => 'contract',
            'category' => 'company',
            'status' => 'published',
            'title' => 'Live',
            'body' => 'Visible.',
            'published_at' => now(),
        ]);
        $this->deleteJson('/api/v1/messages/'.$published->id)->assertUnprocessable();
        $this->assertDatabaseHas('messages', ['id' => $published->id]);

        $this->deleteJson('/api/v1/messages/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('messages', ['id' => $id]);
    }

    public function test_publishing_requires_the_publish_permission(): void
    {
        $permission = Permission::findOrCreate('create messages', 'web');
        $role = Role::findOrCreate('message-writer', 'web');
        $role->syncPermissions([$permission]);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->postJson('/api/v1/messages', [
            'title' => 'Not yet',
            'body' => 'Still a draft.',
            'status' => 'published',
        ])->assertForbidden();

        $this->postJson('/api/v1/messages', [
            'title' => 'Not yet',
            'body' => 'Still a draft.',
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
    }
}
