<?php

namespace Tests\Feature\Employees;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpertiseSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsColleague();
    }

    public function test_ketel_returns_only_active_people_with_a_matching_active_expertise(): void
    {
        [$role, $location] = $this->place();

        $ketel = Skill::factory()->create([
            'name' => 'CV-ketel',
            'code' => 'CVK',
            'category' => 'technical',
            'synonyms' => ['boiler'],
            'is_active' => true,
        ]);
        $heating = Skill::factory()->create([
            'name' => 'Heating',
            'code' => 'HEAT',
            'category' => 'technical',
            'synonyms' => ['ketel'],
            'is_active' => true,
        ]);
        $welding = Skill::factory()->create([
            'name' => 'Lassen',
            'code' => 'LAS',
            'category' => 'technical',
            'synonyms' => [],
            'is_active' => true,
        ]);
        $retired = Skill::factory()->create([
            'name' => 'Ketelonderhoud',
            'code' => 'OLD',
            'category' => 'technical',
            'synonyms' => ['ketel'],
            'is_active' => false,
        ]);
        $steam = Certificate::factory()->create([
            'name' => 'Stoomketel',
            'code' => 'STK',
            'category' => 'technical',
            'synonyms' => [],
            'is_active' => true,
        ]);

        $ada = $this->employee($role, $location, 'Ada', 'Jansen', true);
        $ada->skills()->attach($ketel->id, ['level' => 1]);
        $ada->skills()->attach($welding->id, ['level' => 2]);

        $bram = $this->employee($role, $location, 'Bram', 'Bakker', true);
        $bram->skills()->attach($heating->id, ['level' => 1]);

        $chris = $this->employee($role, $location, 'Chris', 'Cornelisse', true);
        $chris->certificates()->attach($steam->id, []);

        $dirk = $this->employee($role, $location, 'Dirk', 'Dijkstra', false);
        $dirk->skills()->attach($ketel->id, ['level' => 1]);

        $eva = $this->employee($role, $location, 'Eva', 'Evers', true);
        $eva->skills()->attach($retired->id, ['level' => 1]);

        $fay = $this->employee($role, $location, 'Fay', 'Faber', true);
        $fay->skills()->attach($welding->id, ['level' => 1]);

        $response = $this->getJson('/api/v1/expertise-search?query=ketel');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.name', 'Bram Bakker');
        $response->assertJsonPath('data.1.name', 'Chris Cornelisse');
        $response->assertJsonPath('data.2.name', 'Ada Jansen');

        $response->assertJsonPath('data.2.role', $role->name);
        $response->assertJsonPath('data.2.location', $location->name);
        $response->assertJsonPath('data.2.email', 'ada.jansen@hanab.test');
        $response->assertJsonPath('data.2.phone', '+31611111111');
        $response->assertJsonPath('data.2.matches.0.kind', 'skill');
        $response->assertJsonPath('data.2.matches.0.name', 'CV-ketel');
        $response->assertJsonPath('data.2.matches.0.matched_on', 'name');
        $response->assertJsonCount(1, 'data.2.matches');
        $this->assertArrayNotHasKey('team', $response->json('data.2'));

        $response->assertJsonPath('data.0.matches.0.name', 'Heating');
        $response->assertJsonPath('data.0.matches.0.matched_on', 'synonym');
        $response->assertJsonPath('data.0.matches.0.synonym', 'ketel');

        $response->assertJsonPath('data.1.matches.0.kind', 'certificate');
        $response->assertJsonPath('data.1.matches.0.name', 'Stoomketel');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Dirk Dijkstra'));
        $this->assertFalse($names->contains('Eva Evers'));
        $this->assertFalse($names->contains('Fay Faber'));
    }

    public function test_synonym_hit_uses_the_canonical_name_and_is_case_insensitive(): void
    {
        [$role, $location] = $this->place();
        $skill = Skill::factory()->create([
            'name' => 'CV-ketel',
            'code' => 'CVK',
            'category' => 'technical',
            'synonyms' => ['boiler'],
            'is_active' => true,
        ]);
        $employee = $this->employee($role, $location, 'Ada', 'Jansen', true);
        $employee->skills()->attach($skill->id, ['level' => 1]);

        $response = $this->getJson('/api/v1/expertise-search?query=BOILER');

        $response->assertOk();
        $response->assertJsonPath('data.0.matches.0.name', 'CV-ketel');
        $response->assertJsonPath('data.0.matches.0.matched_on', 'synonym');
        $response->assertJsonPath('data.0.matches.0.synonym', 'boiler');
    }

    public function test_code_match_and_a_changed_synonym_or_link_apply_on_the_next_search(): void
    {
        [$role, $location] = $this->place();
        $skill = Skill::factory()->create([
            'name' => 'Heating',
            'code' => 'KTL',
            'category' => 'technical',
            'synonyms' => ['warmte'],
            'is_active' => true,
        ]);
        $employee = $this->employee($role, $location, 'Bram', 'Bakker', true);
        $employee->skills()->attach($skill->id, ['level' => 1]);

        $this->getJson('/api/v1/expertise-search?query=ktl')
            ->assertOk()
            ->assertJsonPath('data.0.matches.0.matched_on', 'code')
            ->assertJsonPath('data.0.matches.0.name', 'Heating');

        $skill->update(['synonyms' => ['furnace']]);

        $this->getJson('/api/v1/expertise-search?query=warmte')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/expertise-search?query=furnace')
            ->assertOk()
            ->assertJsonPath('data.0.matches.0.synonym', 'furnace')
            ->assertJsonPath('data.0.matches.0.name', 'Heating');

        $employee->skills()->detach($skill->id);

        $this->getJson('/api/v1/expertise-search?query=furnace')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_name_finds_the_colleague_without_an_expertise_link(): void
    {
        [$role, $location] = $this->place();
        $this->employee($role, $location, 'Ada', 'Jansen', true);
        $inactive = $this->employee($role, $location, 'Ada', 'Inactive', false);

        $response = $this->getJson('/api/v1/expertise-search?query=jansen');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Ada Jansen');
        $response->assertJsonPath('data.0.role', 'Monteur');
        $response->assertJsonPath('data.0.location', 'Utrecht');
        $response->assertJsonCount(0, 'data.0.matches');
        $this->assertNotSame($inactive->id, $response->json('data.0.id'));
    }

    public function test_the_employee_index_ranks_an_exact_expertise_name_and_explains_it(): void
    {
        [$role, $location] = $this->place();
        $exact = Skill::factory()->create([
            'name' => 'Ketel',
            'code' => 'KTL',
            'category' => 'technical',
            'synonyms' => [],
            'is_active' => true,
        ]);
        $partial = Skill::factory()->create([
            'name' => 'CV-ketel',
            'code' => 'CVK',
            'category' => 'technical',
            'synonyms' => [],
            'is_active' => true,
        ]);
        $specialist = $this->employee($role, $location, 'Ada', 'Jansen', true);
        $specialist->skills()->attach($exact->id, ['level' => 1]);
        $related = $this->employee($role, $location, 'Bram', 'Bakker', true);
        $related->skills()->attach($partial->id, ['level' => 1]);

        $response = $this->getJson('/api/v1/employees?search=ketel&include=skills');

        $response->assertOk();
        $response->assertJsonPath('data.0.first_name', 'Ada');
        $response->assertJsonPath('data.0.match_reason', 'expertise');
        $response->assertJsonPath('data.0.expertise_matches.0.name', 'Ketel');
        $response->assertJsonPath('data.0.expertise_matches.0.score', 100);
        $response->assertJsonPath('data.1.first_name', 'Bram');
    }

    public function test_a_location_finds_colleagues_who_work_there(): void
    {
        [$role, $location] = $this->place();
        $this->employee($role, $location, 'Ada', 'Jansen', true);

        $elsewhere = EmployeeLocation::factory()->create([
            'name' => 'Groningen',
            'is_active' => true,
        ]);
        $this->employee($role, $elsewhere, 'Bram', 'Bakker', true);

        $this->getJson('/api/v1/expertise-search?query=utrecht')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ada Jansen');
    }

    public function test_query_is_required(): void
    {
        $this->getJson('/api/v1/expertise-search')->assertUnprocessable();
    }

    /**
     * @return array{0: EmployeeRole, 1: EmployeeLocation}
     */
    private function place(): array
    {
        $role = EmployeeRole::factory()->create([
            'name' => 'Monteur',
            'is_active' => true,
        ]);
        $location = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'is_active' => true,
        ]);

        return [$role, $location];
    }

    private function employee(
        EmployeeRole $role,
        EmployeeLocation $location,
        string $firstName,
        string $lastName,
        bool $active,
    ): Employee {
        return Employee::factory()->create([
            'employee_role_id' => $role->id,
            'employee_location_id' => $location->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => 'private.'.strtolower($firstName).'.'.strtolower($lastName).'@example.com',
            'phone' => '+31600000000',
            'business_email' => strtolower($firstName).'.'.strtolower($lastName).'@hanab.test',
            'business_phone' => '+31611111111',
            'is_active' => $active,
        ]);
    }
}
