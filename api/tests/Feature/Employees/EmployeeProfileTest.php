<?php

namespace Tests\Feature\Employees;

use Domain\Auth\Models\User;
use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_colleague_profile_hides_the_date_of_birth_and_shows_public_details(): void
    {
        $location = EmployeeLocation::factory()->create(['name' => 'Amsterdam', 'is_active' => true]);
        $role = EmployeeRole::factory()->create(['name' => 'Project Manager', 'is_active' => true]);
        $manager = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'first_name' => 'Nora',
            'last_name' => 'Visser',
            'is_active' => true,
        ]);
        $employee = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'manager_id' => $manager->id,
            'first_name' => 'Casey',
            'last_name' => 'Auer',
            'email' => 'casey.auer@example.com',
            'phone' => '+31612345678',
            'business_email' => 'casey.auer@hanab.test',
            'business_phone' => '+31687654321',
            'introduction' => 'I help colleagues with planning.',
            'date_of_birth' => '1991-04-02',
            'is_active' => true,
        ]);
        $skill = Skill::factory()->create([
            'name' => 'Project management',
            'synonyms' => ['planning', 'coordination'],
            'label_background_color' => '#E0F2FE',
            'is_active' => true,
        ]);
        $certificate = Certificate::factory()->create([
            'name' => 'VCA',
            'label_background_color' => '#DCFCE7',
            'is_active' => true,
        ]);
        $employee->skills()->attach($skill->id, ['level' => 1]);
        $employee->certificates()->attach($certificate->id, [
            'certificate_number' => 'VCA-1',
            'expires_at' => '2027-01-15',
        ]);

        $this->actingAs(User::factory()->create());
        $profile = $this->getJson('/api/v1/employees/'.$employee->id)->assertOk()->json('data');

        $this->assertSame('I help colleagues with planning.', $profile['introduction']);
        $this->assertSame('Casey', $profile['first_name']);
        $this->assertSame('casey.auer@example.com', $profile['email']);
        $this->assertSame('+31612345678', $profile['phone']);
        $this->assertSame('casey.auer@hanab.test', $profile['business_email']);
        $this->assertSame('+31687654321', $profile['business_phone']);
        $this->assertSame('Project Manager', $profile['employee_role']['name']);
        $this->assertSame('Amsterdam', $profile['employee_location']['name']);
        $this->assertSame('Nora', $profile['manager']['first_name']);
        $this->assertSame('Project Manager', $profile['manager']['role']);
        $this->assertSame('Project management', $profile['skills'][0]['name']);
        $this->assertSame('#E0F2FE', $profile['skills'][0]['label_background_color']);
        $this->assertStringContainsString('planning', $profile['skills'][0]['synonyms']);
        $this->assertSame('VCA', $profile['certificates'][0]['name']);
        $this->assertSame('#DCFCE7', $profile['certificates'][0]['label_background_color']);
        $this->assertSame('2027-01-15', $profile['certificates'][0]['expires_at']);
        $this->assertArrayNotHasKey('date_of_birth', $profile);
    }

    public function test_a_colleague_cannot_update_an_employee_record(): void
    {
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'introduction' => 'Original',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        $this->putJson('/api/v1/employees/'.$employee->id, [
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone ?: '0612345678',
            'business_phone' => '0611111111',
            'employee_number' => $employee->employee_number,
            'employment_type' => $employee->employment_type,
            'is_active' => true,
            'introduction' => 'Changed',
        ])->assertForbidden();

        $this->assertSame('Original', $employee->fresh()->introduction);
    }

    public function test_a_colleague_cannot_open_an_inactive_profile(): void
    {
        $location = EmployeeLocation::factory()->create(['is_active' => true]);
        $role = EmployeeRole::factory()->create(['is_active' => true]);
        $manager = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'is_active' => false,
        ]);
        $employee = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'manager_id' => $manager->id,
            'is_active' => false,
        ]);

        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/employees/'.$employee->id)->assertNotFound();

        $active = Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);
        $this->getJson('/api/v1/employees/'.$active->id)
            ->assertOk()
            ->assertJsonPath('data.manager', null);

        $this->actingAsSuperAdmin();
        $this->getJson('/api/v1/employees/'.$employee->id)
            ->assertOk()
            ->assertJsonPath('data.manager.id', $manager->id);
    }
}
