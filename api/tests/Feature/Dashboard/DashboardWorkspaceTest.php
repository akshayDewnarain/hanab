<?php

namespace Tests\Feature\Dashboard;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Domain\Messages\Models\Message;
use Domain\Messages\Models\MessagePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_dashboard_actions_or_activity(): void
    {
        $this->getJson('/api/v1/dashboard/actions')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard/activity')->assertUnauthorized();
    }

    public function test_actions_follow_the_records_and_stay_hidden_without_permission(): void
    {
        $role = EmployeeRole::factory()->create();
        $office = EmployeeLocation::factory()->create([
            'latitude' => 52.09,
            'longitude' => 5.12,
            'is_active' => true,
        ]);
        EmployeeLocation::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ]);
        $skill = Skill::factory()->create();
        $certificate = Certificate::factory()->create();

        $gap = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $office->id,
            'business_email' => null,
            'business_phone' => null,
            'is_active' => true,
        ]);
        $complete = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $office->id,
            'business_email' => 'complete@hanab.test',
            'business_phone' => '+31600000000',
            'is_active' => true,
        ]);
        $complete->skills()->attach($skill->id, ['level' => 1]);
        $complete->certificates()->attach($certificate->id);

        $this->actingAsColleague();
        $this->getJson('/api/v1/dashboard/actions')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonMissingPath('data.0.count');

        $this->actingAsSuperAdmin();
        $keys = collect($this->getJson('/api/v1/dashboard/actions')->assertOk()->json('data'))->pluck('key');
        $this->assertTrue($keys->contains('missing_skills'));
        $this->assertTrue($keys->contains('missing_certificates'));
        $this->assertTrue($keys->contains('missing_business_phone'));
        $this->assertTrue($keys->contains('missing_business_email'));
        $this->assertTrue($keys->contains('locations_without_coordinates'));
        $this->assertFalse($keys->contains('missing_manager'));

        $gap->skills()->attach($skill->id, ['level' => 1]);
        $gap->certificates()->attach($certificate->id);
        $gap->update([
            'business_email' => 'gap@hanab.test',
            'business_phone' => '+31600000001',
        ]);

        $remaining = collect($this->getJson('/api/v1/dashboard/actions')->assertOk()->json('data'))->pluck('key');
        $this->assertFalse($remaining->contains('missing_skills'));
        $this->assertFalse($remaining->contains('missing_certificates'));
        $this->assertFalse($remaining->contains('missing_business_phone'));
        $this->assertTrue($remaining->contains('locations_without_coordinates'));

        $this->getJson('/api/v1/employees?filter[missing_business_email]=1&filter[is_active]=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_activity_uses_published_messages_and_hides_private_dates(): void
    {
        $role = EmployeeRole::factory()->create();
        $office = EmployeeLocation::factory()->create(['is_active' => true]);
        $active = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $office->id,
            'first_name' => 'Eva',
            'last_name' => 'Willems',
            'date_of_birth' => '1990-04-02',
            'is_active' => true,
        ]);
        $inactive = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $office->id,
            'is_active' => false,
        ]);

        Message::query()->create([
            'type' => Message::TYPE_INTRODUCTION,
            'category' => Message::CATEGORY_PEOPLE,
            'status' => Message::STATUS_DRAFT,
            'title' => 'New colleague',
            'body' => 'Draft must stay out of the feed.',
            'subject_name' => 'Draft Person',
            'subject_employee_id' => $active->id,
            'dedupe_key' => 'draft-intro',
        ]);
        Message::query()->create([
            'type' => Message::TYPE_INTRODUCTION,
            'category' => Message::CATEGORY_PEOPLE,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'New colleague',
            'body' => 'Eva Willems joined as Monteur.',
            'subject_name' => 'Eva Willems',
            'detail' => 'Monteur',
            'subject_employee_id' => $active->id,
            'published_at' => now()->subHour(),
            'dedupe_key' => 'published-intro',
        ]);
        Message::query()->create([
            'type' => Message::TYPE_BIRTHDAY,
            'category' => Message::CATEGORY_MILESTONES,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Birthday',
            'body' => 'Eva Willems is celebrating a birthday.',
            'subject_name' => 'Eva Willems',
            'subject_employee_id' => $active->id,
            'published_at' => now(),
            'dedupe_key' => 'birthday',
        ]);
        Message::query()->create([
            'type' => Message::TYPE_CERTIFICATE,
            'category' => Message::CATEGORY_MILESTONES,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'New certificate',
            'body' => 'Former colleague earned a certificate.',
            'subject_name' => 'Former',
            'subject_employee_id' => $inactive->id,
            'published_at' => now(),
            'dedupe_key' => 'inactive-certificate',
        ]);

        $user = $this->actingAsColleague();
        $response = $this->getJson('/api/v1/dashboard/activity')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'birthday')
            ->assertJsonPath('data.0.subject_employee_id', $active->id)
            ->assertJsonPath('data.1.type', 'introduction');

        $this->assertStringNotContainsString('1990-04-02', $response->getContent());
        $this->assertStringNotContainsString('Draft must stay out', $response->getContent());
        $this->assertStringNotContainsString('Former colleague', $response->getContent());

        MessagePreference::query()->where('user_id', $user->id)->update(['milestones' => false]);

        $this->getJson('/api/v1/dashboard/activity')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'introduction');
    }

    public function test_activity_pages_by_cursor_and_returns_only_newer_events(): void
    {
        $this->actingAsColleague();
        foreach (range(1, 9) as $index) {
            Message::query()->create([
                'type' => Message::TYPE_CONTRACT,
                'category' => Message::CATEGORY_COMPANY,
                'status' => Message::STATUS_PUBLISHED,
                'title' => 'Notice '.$index,
                'body' => 'Published notice '.$index,
                'published_at' => now()->subMinutes(20 - $index),
                'dedupe_key' => 'notice-'.$index,
            ]);
        }

        $first = $this->getJson('/api/v1/dashboard/activity?limit=4')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('data.0.title', 'Notice 9');

        $cursor = $first->json('meta.next_cursor');
        $newest = $first->json('meta.newest_cursor');
        $this->assertIsString($cursor);
        $this->assertIsString($newest);

        $second = $this->getJson('/api/v1/dashboard/activity?limit=4&cursor='.$cursor)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.title', 'Notice 5');

        $firstIds = collect($first->json('data'))->pluck('id');
        $secondIds = collect($second->json('data'))->pluck('id');
        $this->assertCount(0, $firstIds->intersect($secondIds));

        $third = $this->getJson('/api/v1/dashboard/activity?limit=4&cursor='.$second->json('meta.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('data.0.title', 'Notice 1');

        Message::query()->create([
            'type' => Message::TYPE_CONTRACT,
            'category' => Message::CATEGORY_COMPANY,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Notice 10',
            'body' => 'The latest notice.',
            'published_at' => now(),
            'dedupe_key' => 'notice-10',
        ]);

        $this->getJson('/api/v1/dashboard/activity?after='.$newest)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Notice 10')
            ->assertJsonPath('meta.has_more', false);
    }
}
