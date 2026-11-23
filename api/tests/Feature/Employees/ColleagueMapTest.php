<?php

namespace Tests\Feature\Employees;

use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColleagueMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_location_rejects_an_incomplete_or_impossible_coordinate(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/api/v1/employee-locations', [
            'name' => 'Utrecht',
            'cluster_name' => 'Central',
            'is_active' => true,
            'latitude' => 52.09,
        ])->assertUnprocessable()->assertJsonValidationErrors(['longitude']);

        $this->postJson('/api/v1/employee-locations', [
            'name' => 'Utrecht',
            'cluster_name' => 'Central',
            'is_active' => true,
            'latitude' => 91,
            'longitude' => 5.12,
        ])->assertUnprocessable()->assertJsonValidationErrors(['latitude']);
    }

    public function test_a_location_can_be_saved_without_coordinates(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/api/v1/employee-locations', [
            'name' => 'Utrecht',
            'cluster_name' => 'Central',
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null);
    }

    public function test_the_map_counts_matching_colleagues_and_skips_offices_without_coordinates(): void
    {
        $this->actingAsColleague();
        $role = EmployeeRole::factory()->create(['name' => 'Monteur', 'is_active' => true]);
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'latitude' => 52.0907,
            'longitude' => 5.1214,
            'is_active' => true,
        ]);
        $eindhoven = EmployeeLocation::factory()->create([
            'name' => 'Eindhoven',
            'latitude' => 51.4416,
            'longitude' => 5.4697,
            'is_active' => true,
        ]);
        EmployeeLocation::factory()->create([
            'name' => 'Unmapped',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ]);
        $skill = Skill::factory()->create([
            'name' => 'Ketels',
            'code' => 'KTL',
            'synonyms' => ['ketel'],
            'is_active' => true,
        ]);

        $ada = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $utrecht->id,
            'is_active' => true,
        ]);
        $ada->skills()->attach($skill->id, ['level' => 1]);
        $bram = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $utrecht->id,
            'is_active' => true,
        ]);
        $bram->skills()->attach($skill->id, ['level' => 1]);
        $inactive = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $eindhoven->id,
            'is_active' => false,
        ]);
        $inactive->skills()->attach($skill->id, ['level' => 1]);

        $response = $this->getJson('/api/v1/employees/locations?search=ketel')->assertOk();
        $byName = collect($response->json('data'))->keyBy('name');

        $this->assertSame(2, $byName['Utrecht']['employee_count']);
        $this->assertSame(0, $byName['Eindhoven']['employee_count']);
        $this->assertFalse($byName->has('Unmapped'));
    }
}
