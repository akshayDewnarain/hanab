<?php

namespace Tests\Feature\Employees;

use Domain\Auth\Models\User;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\OrganizationLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganogramTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chart_follows_role_parents_and_skips_inactive_people(): void
    {
        $location = EmployeeLocation::factory()->create(['name' => 'Utrecht', 'is_active' => true]);
        $lead = EmployeeRole::factory()->create(['name' => 'Site lead', 'is_active' => true, 'sort_order' => 1]);
        $fitter = EmployeeRole::factory()->create([
            'name' => 'Fitter',
            'is_active' => true,
            'parent_id' => $lead->id,
            'sort_order' => 2,
        ]);
        EmployeeRole::factory()->create(['name' => 'Retired title', 'is_active' => false, 'parent_id' => $lead->id]);

        Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Jansen',
            'employee_role_id' => $fitter->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
        ]);
        Employee::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Person',
            'employee_role_id' => $fitter->id,
            'employee_location_id' => $location->id,
            'is_active' => false,
        ]);

        $this->actingAsColleague();
        $response = $this->getJson('/api/v1/organogram?view=roles')->assertOk();

        $response->assertJsonPath('meta.view', 'roles');
        $roles = collect($response->json('data.nodes'))->keyBy('name');
        $this->assertSame('role-'.$lead->id, $roles['Site lead']['id']);
        $this->assertSame('Ada Jansen', $roles['Fitter']['people'][0]['name']);
        $this->assertSame('Utrecht', $roles['Fitter']['people'][0]['location']);
        $this->assertFalse($roles->has('Retired title'));
        $this->assertSame(['Ada Jansen'], collect($roles['Fitter']['people'])->pluck('name')->all());
        $edges = collect($response->json('data.edges'));
        $this->assertTrue($edges->contains(fn (array $edge): bool => $edge['source'] === 'role-'.$lead->id && $edge['target'] === 'role-'.$fitter->id && $edge['type'] === 'role_parent'));
        $this->assertArrayNotHasKey('date_of_birth', $roles['Fitter']['people'][0]);
    }

    public function test_only_an_editor_receives_the_date_of_birth(): void
    {
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'date_of_birth' => '1990-11-08',
            'is_active' => true,
        ]);

        $this->actingAsSuperAdmin();
        $visible = $this->getJson('/api/v1/employees/'.$employee->id)->assertOk()->json('data.date_of_birth');
        $this->assertIsString($visible);
        $this->assertStringContainsString('1990', $visible);

        $this->actingAs(User::factory()->create());
        $hidden = $this->getJson('/api/v1/employees/'.$employee->id)->assertOk()->json('data');
        $this->assertArrayNotHasKey('date_of_birth', $hidden);
    }

    public function test_a_role_cannot_sit_under_itself(): void
    {
        $this->actingAsSuperAdmin();
        $lead = EmployeeRole::factory()->create(['name' => 'Site lead', 'is_active' => true]);
        $fitter = EmployeeRole::factory()->create([
            'name' => 'Fitter',
            'is_active' => true,
            'parent_id' => $lead->id,
        ]);

        $this->putJson('/api/v1/employee-roles/'.$lead->id, [
            'name' => 'Site lead',
            'is_active' => true,
            'parent_id' => $fitter->id,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_id');
    }

    public function test_a_guest_cannot_change_a_role(): void
    {
        $role = EmployeeRole::factory()->create(['name' => 'Fitter', 'is_active' => true]);

        $this->putJson('/api/v1/employee-roles/'.$role->id, [
            'name' => 'Planner',
            'is_active' => true,
        ])->assertUnauthorized();

        $this->actingAsColleague();
        $this->putJson('/api/v1/employee-roles/'.$role->id, [
            'name' => 'Planner',
            'is_active' => true,
        ])->assertForbidden();

        $this->assertSame('Fitter', $role->fresh()->name);
    }

    public function test_people_with_the_same_role_can_report_to_different_managers(): void
    {
        $location = EmployeeLocation::factory()->create(['name' => 'Amsterdam', 'is_active' => true]);
        $other = EmployeeLocation::factory()->create(['name' => 'Rotterdam', 'is_active' => true]);
        $role = EmployeeRole::factory()->create(['name' => 'Engineer', 'is_active' => true]);
        $lead = Employee::factory()->create([
            'first_name' => 'Nora',
            'last_name' => 'Lead',
            'employee_role_id' => $role->id,
            'employee_location_id' => $other->id,
            'is_active' => true,
            'manager_id' => null,
        ]);
        $ada = Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Jansen',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'manager_id' => $lead->id,
        ]);
        $ben = Employee::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'Vos',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'manager_id' => null,
        ]);
        Employee::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Person',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => false,
            'manager_id' => $lead->id,
        ]);

        $this->actingAsColleague();
        $response = $this->getJson('/api/v1/organogram')->assertOk();
        $response->assertJsonPath('meta.view', 'management');
        $nodes = collect($response->json('data.nodes'))->keyBy('employeeId');
        $this->assertSame('Engineer', $nodes[$ada->id]['role']);
        $this->assertSame('Engineer', $nodes[$ben->id]['role']);
        $this->assertFalse($nodes->has(
            Employee::query()->where('last_name', 'Person')->value('id'),
        ));
        $this->assertArrayNotHasKey('date_of_birth', $nodes[$ada->id]);
        $this->assertArrayNotHasKey('employeeNumber', $nodes[$ada->id]);

        $edges = collect($response->json('data.edges'));
        $this->assertTrue($edges->contains(fn (array $edge): bool => $edge['source'] === 'employee-'.$lead->id && $edge['target'] === 'employee-'.$ada->id));
        $this->assertFalse($edges->contains(fn (array $edge): bool => $edge['target'] === 'employee-'.$ben->id));

        $filtered = $this->getJson('/api/v1/organogram?location_id='.$location->id)->assertOk();
        $filteredNodes = collect($filtered->json('data.nodes'))->keyBy('employeeId');
        $this->assertTrue($filteredNodes[$ada->id]['context'] === false);
        $this->assertTrue($filteredNodes[$lead->id]['context'] === true);
        $this->assertSame('Rotterdam', $filteredNodes[$lead->id]['location']);
        $this->assertSame('Amsterdam', $filteredNodes[$ben->id]['location']);
        $this->assertFalse($filteredNodes[$ben->id]['context']);
    }

    public function test_an_inactive_manager_stays_stored_and_is_not_drawn_as_a_line(): void
    {
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $manager = Employee::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Manager',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => false,
        ]);
        $person = Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Jansen',
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'manager_id' => $manager->id,
        ]);

        $this->actingAsColleague();
        $node = collect($this->getJson('/api/v1/organogram')->assertOk()->json('data.nodes'))
            ->firstWhere('employeeId', $person->id);

        $this->assertSame('inactive', $node['managerNote']);
        $this->assertSame('Old Manager', $node['managerName']);
        $this->assertSame($manager->id, $person->fresh()->manager_id);
        $this->assertSame([], $this->getJson('/api/v1/organogram')->json('data.edges'));
    }

    public function test_a_reporting_line_cannot_loop(): void
    {
        $this->actingAsSuperAdmin();
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $lead = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
        ]);
        $report = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'manager_id' => $lead->id,
        ]);

        $this->putJson('/api/v1/employees/'.$lead->id, $this->employeePayload($lead, [
            'manager_id' => $report->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('manager_id');

        $this->putJson('/api/v1/employees/'.$report->id, $this->employeePayload($report, [
            'manager_id' => $report->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('manager_id');

        $this->actingAsColleague();
        $this->putJson('/api/v1/employees/'.$report->id, $this->employeePayload($report, [
            'manager_id' => null,
        ]))->assertForbidden();
        $this->assertSame($lead->id, $report->fresh()->manager_id);
    }

    public function test_organization_levels_are_visual_and_permissioned(): void
    {
        $this->actingAsSuperAdmin();
        $created = $this->postJson('/api/v1/organization-levels', [
            'name' => 'Crew',
            'sort_order' => 60,
            'color' => '#112233',
            'is_active' => true,
        ])->assertCreated();

        $this->postJson('/api/v1/organization-levels', [
            'name' => 'Broken',
            'color' => 'blue',
            'is_active' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('color');

        $level = OrganizationLevel::query()->findOrFail($created->json('data.id'));
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true, 'organization_level_id' => $level->id]);
        $override = OrganizationLevel::factory()->create(['name' => 'Override', 'color' => '#445566', 'is_active' => true]);
        $person = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => true,
            'organization_level_id' => $override->id,
        ]);

        $this->actingAsColleague();
        $node = collect($this->getJson('/api/v1/organogram')->assertOk()->json('data.nodes'))
            ->firstWhere('employeeId', $person->id);
        $this->assertSame('Override', $node['level']['name']);
        $this->assertSame('#445566', $node['level']['color']);

        $this->postJson('/api/v1/organization-levels', [
            'name' => 'Denied',
            'color' => '#000000',
            'is_active' => true,
        ])->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function employeePayload(Employee $employee, array $overrides): array
    {
        return [
            'employee_role_id' => $employee->employee_role_id,
            'employee_location_id' => $employee->employee_location_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone ?: '0612345678',
            'business_phone' => $employee->business_phone ?: '0611111111',
            'employee_number' => $employee->employee_number,
            'employment_type' => $employee->employment_type,
            'is_active' => $employee->is_active,
            ...$overrides,
        ];
    }
}
