<?php

namespace Tests\Feature\Employees;

use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeImportLog;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Domain\Employees\Support\EmployeeImportMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperAdmin();
    }

    public function test_employee_fields_follow_the_hanab_matrix_and_include_an_example_row(): void
    {
        $fields = collect($this->getJson('/api/v1/transfers/employees/fields')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame('hanab', $fields['employee_number']['owner']);
        $this->assertTrue($fields['employee_number']['required']);
        $this->assertFalse($fields['phone']['required']);
        $this->assertFalse($fields['initials']['required']);
        $this->assertSame('A.J.', $fields['initials']['example']);
        $this->assertFalse($fields['start_date']['required']);
        $this->assertSame('EXAMPLE-001', $fields['employee_number']['example']);
        $this->assertSame('Example role', $fields['role']['example']);
        $this->assertArrayNotHasKey('photo', $fields);
        $this->assertArrayNotHasKey('employee_role_id', $fields);
        $this->assertSame('employee', EmployeeImportMatrix::HELD_BACK['photo']);
        $this->assertSame('employee', EmployeeImportMatrix::HELD_BACK['notification_preferences']);
    }

    public function test_a_first_file_creates_an_employee_and_a_second_file_updates_the_role_without_clearing_skills_or_photo(): void
    {
        Storage::fake('public');
        [$monteur, $planner, $amsterdam] = $this->places();
        $skill = Skill::factory()->create(['name' => 'CV-ketel', 'category' => 'technical', 'is_active' => true]);

        $created = $this->postJson('/api/v1/transfers/employees/commit', [
            'file_name' => 'first.csv',
            'rows' => [[
                'employee_number' => 'EMP-100',
                'first_name' => 'Ada',
                'last_name' => 'Jansen',
                'initials' => 'A.J.',
                'email' => 'ada@hanab.test',
                'phone' => '0611111111',
                'role' => 'Monteur',
                'location' => 'Amsterdam',
                'employment_type' => 'internal',
                'is_active' => 'ja',
            ]],
        ]);

        $created->assertOk()->assertJsonPath('data.saved', true)->assertJsonPath('data.created', 1);
        $employee = Employee::query()->where('employee_number', 'EMP-100')->firstOrFail();
        $employee->skills()->attach($skill->id, ['level' => 4]);
        $employee->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('image');

        $preview = $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [[
                'employee_number' => 'EMP-100',
                'role' => 'Planner',
                'is_active' => 'left',
            ]],
        ]);
        $preview->assertOk()
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.invalid', 0);

        $this->postJson('/api/v1/transfers/employees/commit', [
            'file_name' => 'change.csv',
            'rows' => [[
                'employee_number' => 'EMP-100',
                'role' => 'Planner',
                'is_active' => 'left',
            ]],
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $employee->refresh();
        $this->assertSame($planner->id, $employee->employee_role_id);
        $this->assertSame($monteur->id, EmployeeRole::query()->where('name', 'Monteur')->first()->id);
        $this->assertSame($amsterdam->id, $employee->employee_location_id);
        $this->assertSame('0611111111', $employee->phone);
        $this->assertSame('A.J.', $employee->initials);
        $this->assertFalse($employee->is_active);
        $this->assertSame(1, $employee->skills()->count());
        $this->assertNotNull($employee->getFirstMedia('image'));
        $this->assertSame(0, Employee::query()->matchingExpertise('ketel')->count());
        $this->assertSame(1, Employee::query()->count());

        $log = EmployeeImportLog::query()->where('file_name', 'change.csv')->first();
        $this->assertSame('committed', $log->outcome);
        $this->assertSame(1, $log->updated_count);
    }

    public function test_a_bad_file_and_a_duplicate_employee_number_change_nothing(): void
    {
        [$monteur, $planner, $amsterdam] = $this->places();
        $before = Employee::query()->count();

        $duplicate = $this->postJson('/api/v1/transfers/employees/commit', [
            'file_name' => 'duplicates.csv',
            'rows' => [
                $this->person('EMP-200', 'Bram', 'Bakker', 'bram@hanab.test'),
                $this->person('EMP-200', 'Bram', 'Bakker', 'bram.other@hanab.test'),
            ],
        ]);

        $duplicate->assertStatus(422)->assertJsonPath('data.saved', false);
        $this->assertSame($before, Employee::query()->count());
        $this->assertSame('rejected', EmployeeImportLog::query()->where('file_name', 'duplicates.csv')->first()->outcome);

        $unknown = $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [
                $this->person('EMP-201', 'Chris', 'Cornelisse', 'chris@hanab.test', 'Missing role'),
            ],
        ]);
        $unknown->assertOk()->assertJsonPath('data.invalid', 1);
        $this->assertStringContainsString('Unknown Role', $unknown->json('data.errors.0.messages.0'));

        $mixed = $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [
                $this->person('EMP-202', 'Dirk', 'Dekker', 'dirk@hanab.test'),
                ['employee_number' => 'EMP-203', 'first_name' => 'Eva'],
            ],
        ]);
        $mixed->assertStatus(422);
        $this->assertSame($before, Employee::query()->count());

        $this->assertSame($monteur->name, 'Monteur');
        $this->assertSame($planner->name, 'Planner');
        $this->assertSame($amsterdam->name, 'Amsterdam');
    }

    public function test_preview_names_the_fields_an_update_will_change(): void
    {
        $this->places();
        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [$this->person('EMP-300', 'Fay', 'Fransen', 'fay@hanab.test')],
        ])->assertOk();

        $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [[
                'employee_number' => 'EMP-300',
                'phone' => '0612345678',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.updates.0.employee_number', 'EMP-300')
            ->assertJsonPath('data.updates.0.fields.0', 'phone');

        $this->assertSame(1, Employee::query()->count());
        $this->assertNull(Employee::query()->where('employee_number', 'EMP-300')->value('phone'));
    }

    public function test_export_respects_the_active_filter_and_includes_the_manager_number(): void
    {
        $this->places();
        $inactive = $this->person('EMP-2', 'Ada', 'Jansen', 'ada@hanab.test');
        $inactive['is_active'] = 'false';
        $inactive['manager_employee_number'] = 'EMP-1';

        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [
                $this->person('EMP-1', 'Nora', 'Lead', 'nora@hanab.test'),
                $inactive,
            ],
        ])->assertOk();

        $this->getJson('/api/v1/transfers/employees/rows?filter[is_active]=0')
            ->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.employee_number', 'EMP-2')
            ->assertJsonPath('data.rows.0.manager_employee_number', 'EMP-1');
    }

    public function test_export_uses_role_and_location_names(): void
    {
        $this->places();
        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [$this->person('EMP-300', 'Fay', 'Fransen', 'fay@hanab.test')],
        ])->assertOk();

        $this->getJson('/api/v1/transfers/employees/rows')
            ->assertOk()
            ->assertJsonPath('data.rows.0.employee_number', 'EMP-300')
            ->assertJsonPath('data.rows.0.role', 'Monteur')
            ->assertJsonPath('data.rows.0.location', 'Amsterdam')
            ->assertJsonPath('data.rows.0.is_active', 'true');
    }

    public function test_a_manager_number_is_optional_and_a_file_without_it_keeps_the_current_manager(): void
    {
        $this->places();
        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [
                $this->person('EMP-1', 'Nora', 'Lead', 'nora@hanab.test'),
                $this->person('EMP-2', 'Ada', 'Jansen', 'ada@hanab.test') + ['manager_employee_number' => 'EMP-1'],
            ],
        ])->assertOk()->assertJsonPath('data.saved', true);

        $ada = Employee::query()->where('employee_number', 'EMP-2')->firstOrFail();
        $nora = Employee::query()->where('employee_number', 'EMP-1')->firstOrFail();
        $this->assertSame($nora->id, $ada->manager_id);

        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [[
                'employee_number' => 'EMP-2',
                'phone' => '0612345678',
            ]],
        ])->assertOk()->assertJsonPath('data.saved', true);

        $this->assertSame($nora->id, $ada->fresh()->manager_id);
        $this->assertSame('0612345678', $ada->fresh()->phone);
    }

    public function test_an_unknown_manager_and_a_reporting_loop_reject_the_file(): void
    {
        $this->places();
        $preview = $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [
                $this->person('EMP-9', 'Ada', 'Jansen', 'ada@hanab.test') + ['manager_employee_number' => 'MISSING'],
            ],
        ])->assertOk();
        $this->assertGreaterThan(0, $preview->json('data.invalid'));

        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [
                $this->person('EMP-1', 'Nora', 'Lead', 'nora@hanab.test') + ['manager_employee_number' => 'EMP-2'],
                $this->person('EMP-2', 'Ada', 'Jansen', 'ada@hanab.test') + ['manager_employee_number' => 'EMP-1'],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Employee::query()->count());
    }

    public function test_an_empty_manager_cell_clears_the_relationship(): void
    {
        $this->places();
        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [
                $this->person('EMP-1', 'Nora', 'Lead', 'nora@hanab.test'),
                $this->person('EMP-2', 'Ada', 'Jansen', 'ada@hanab.test') + ['manager_employee_number' => 'EMP-1'],
            ],
        ])->assertOk();

        $this->postJson('/api/v1/transfers/employees/preview', [
            'rows' => [[
                'employee_number' => 'EMP-2',
                'manager_employee_number' => '',
            ]],
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [[
                'employee_number' => 'EMP-2',
                'manager_employee_number' => '',
            ]],
        ])->assertOk()->assertJsonPath('data.saved', true);

        $this->assertNull(Employee::query()->where('employee_number', 'EMP-2')->value('manager_id'));
    }

    public function test_employee_strategies_keep_number_matching_and_refuse_a_partial_import(): void
    {
        $this->places();
        $this->postJson('/api/v1/transfers/employees/commit', [
            'rows' => [$this->person('EMP-400', 'Gijs', 'Groen', 'gijs@hanab.test')],
        ])->assertOk();

        $this->postJson('/api/v1/transfers/employees/commit', [
            'strategy' => 'create_only',
            'rows' => [[
                'employee_number' => 'EMP-400',
                'role' => 'Planner',
            ]],
        ])->assertOk()->assertJsonPath('data.skipped', 1)->assertJsonPath('data.updated', 0);

        $this->assertSame(
            EmployeeRole::query()->where('name', 'Monteur')->value('id'),
            Employee::query()->where('employee_number', 'EMP-400')->value('employee_role_id'),
        );

        $this->postJson('/api/v1/transfers/employees/commit', [
            'strategy' => 'update_only',
            'rows' => [$this->person('EMP-401', 'Henk', 'Hoog', 'henk@hanab.test')],
        ])->assertOk()->assertJsonPath('data.skipped', 1)->assertJsonPath('data.created', 0);
        $this->assertNull(Employee::query()->where('employee_number', 'EMP-401')->first());

        $this->postJson('/api/v1/transfers/employees/commit', [
            'policy' => 'valid_rows',
            'confirm_partial' => true,
            'rows' => [$this->person('EMP-402', 'Iris', 'Iets', 'iris@hanab.test')],
        ])->assertStatus(422)->assertJsonPath('data.saved', false);
        $this->assertNull(Employee::query()->where('employee_number', 'EMP-402')->first());
    }

    /**
     * @return array{0: EmployeeRole, 1: EmployeeRole, 2: EmployeeLocation}
     */
    private function places(): array
    {
        return [
            EmployeeRole::factory()->create(['name' => 'Monteur', 'is_active' => true]),
            EmployeeRole::factory()->create(['name' => 'Planner', 'is_active' => true]),
            EmployeeLocation::factory()->create(['name' => 'Amsterdam', 'cluster_name' => 'West', 'is_active' => true]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function person(string $number, string $first, string $last, string $email, string $role = 'Monteur'): array
    {
        return [
            'employee_number' => $number,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'role' => $role,
            'location' => 'Amsterdam',
            'employment_type' => 'internal',
            'is_active' => 'true',
        ];
    }
}
