<?php

namespace Tests\Feature\Dashboard;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_the_people_overview(): void
    {
        $this->getJson('/api/v1/dashboard/people-overview')->assertUnauthorized();
    }

    public function test_the_overview_counts_active_employees_once_and_maps_offices_with_coordinates(): void
    {
        $this->actingAsSuperAdmin();
        $role = EmployeeRole::factory()->create();
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'latitude' => 52.0907,
            'longitude' => 5.1214,
            'is_active' => true,
        ]);
        $unmapped = EmployeeLocation::factory()->create([
            'name' => 'Unmapped',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ]);
        $skill = Skill::factory()->create();
        $secondSkill = Skill::factory()->create();
        $certificate = Certificate::factory()->create();

        $complete = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $utrecht->id,
            'phone' => '+31612345678',
            'is_active' => true,
        ]);
        $complete->skills()->attach([$skill->id => ['level' => 1], $secondSkill->id => ['level' => 1]]);
        $complete->certificates()->attach($certificate->id);

        $gap = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $unmapped->id,
            'phone' => null,
            'is_active' => true,
        ]);

        $partial = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $utrecht->id,
            'phone' => '',
            'is_active' => true,
        ]);
        $partial->skills()->attach($skill->id, ['level' => 1]);

        $inactive = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $utrecht->id,
            'phone' => null,
            'is_active' => false,
        ]);

        $overview = $this->getJson('/api/v1/dashboard/people-overview')
            ->assertOk()
            ->assertJsonPath('data.active_employees', 3)
            ->assertJsonPath('data.without_skills', 1)
            ->assertJsonPath('data.without_certificates', 2)
            ->assertJsonPath('data.without_location', 0)
            ->assertJsonPath('data.location_percent', 100)
            ->assertJsonPath('data.without_email', 0)
            ->assertJsonPath('data.without_phone', 2)
            ->assertJsonPath('data.with_skills', 2)
            ->assertJsonPath('data.skills_percent', 67)
            ->assertJsonPath('data.locations', 2)
            ->assertJsonPath('data.locations_without_coordinates', 1)
            ->assertJsonPath('data.quality', true)
            ->assertJsonPath('data.work_locations.0.name', 'Utrecht')
            ->assertJsonPath('data.work_locations.0.employee_count', 2)
            ->json('data');

        $this->assertCount(1, $overview['work_locations']);
        $this->assertArrayNotHasKey('first_name', $overview);
        $this->assertNotContains($inactive->id, collect($overview['work_locations'])->pluck('id'));
        $this->assertNotSame($unmapped->id, $overview['work_locations'][0]['id']);
        $this->assertNotSame($gap->id, $complete->id);

        $this->getJson('/api/v1/employees?filter[missing_skill]=1&filter[is_active]=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $gap->id);

        $this->getJson('/api/v1/employees?filter[missing_phone]=1&filter[is_active]=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_an_empty_organization_reports_zero_without_a_percentage_error(): void
    {
        $this->actingAsColleague();

        $this->getJson('/api/v1/dashboard/people-overview')
            ->assertOk()
            ->assertJsonPath('data.active_employees', 0)
            ->assertJsonPath('data.quality', false)
            ->assertJsonPath('data.locations', 0)
            ->assertJsonPath('data.work_locations', [])
            ->assertJsonMissingPath('data.without_skills');
    }
}
