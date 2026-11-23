<?php

namespace Tests\Feature\Support;

use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperAdmin();
    }

    public function test_every_registered_model_exposes_its_existing_fields(): void
    {
        $models = $this->getJson('/api/v1/transfers/models')->assertOk()->json('data');
        $keys = array_column($models, 'key');

        $this->assertEqualsCanonicalizing([
            'employees',
            'employee-roles',
            'employee-locations',
            'skills',
            'certificates',
            'employee-skills',
            'employee-certificates',
        ], $keys);

        $fields = $this->getJson('/api/v1/transfers/skills/fields')->assertOk()->json('data');
        $byId = collect($fields)->keyBy('id');

        $this->assertTrue($byId['name']['required']);
        $this->assertFalse($byId['code']['required']);
        $this->assertSame('array', $byId['synonyms']['type']);
        $this->assertSame('boolean', $byId['is_active']['type']);
    }

    public function test_preview_checks_existing_rules_and_a_bad_row_writes_nothing(): void
    {
        $payload = [
            'rows' => [
                [
                    'name' => 'CV-ketel',
                    'category' => 'technical',
                    'is_active' => 'ja',
                    'synonyms' => 'ketel, boiler',
                ],
                [
                    'name' => '',
                    'category' => 'technical',
                    'is_active' => 'true',
                ],
            ],
        ];

        $preview = $this->postJson('/api/v1/transfers/skills/preview', $payload);

        $preview->assertOk();
        $preview->assertJsonPath('data.valid', 1);
        $preview->assertJsonPath('data.invalid', 1);

        $commit = $this->postJson('/api/v1/transfers/skills/commit', $payload);

        $commit->assertStatus(422);
        $commit->assertJsonPath('data.created', 0);
        $this->assertSame(0, Skill::query()->count());
    }

    public function test_commit_creates_records_for_the_chosen_model_and_export_reads_them_back(): void
    {
        $this->postJson('/api/v1/transfers/skills/commit', [
            'rows' => [[
                'name' => 'CV-ketel',
                'category' => 'technical',
                'is_active' => 'true',
                'synonyms' => 'ketel, boiler',
            ]],
        ])->assertOk()->assertJsonPath('data.created', 1);

        $skill = Skill::query()->first();
        $this->assertSame('CV-ketel', $skill->name);
        $this->assertSame(['ketel', 'boiler'], $skill->synonyms);
        $this->assertTrue($skill->is_active);

        $this->postJson('/api/v1/transfers/employee-roles/commit', [
            'rows' => [[
                'name' => 'Monteur',
                'is_active' => 'nee',
            ]],
        ])->assertOk();

        $this->assertFalse(EmployeeRole::query()->first()->is_active);

        $export = $this->getJson('/api/v1/transfers/skills/rows')->assertOk();
        $export->assertJsonPath('data.rows.0.name', 'CV-ketel');
        $export->assertJsonPath('data.rows.0.synonyms', 'ketel, boiler');
        $export->assertJsonPath('data.rows.0.is_active', 'true');
    }

    public function test_unknown_fields_and_unknown_models_are_rejected(): void
    {
        $this->postJson('/api/v1/transfers/skills/preview', [
            'rows' => [[
                'name' => 'CV-ketel',
                'nickname' => 'ketel',
            ]],
        ])->assertOk()->assertJsonPath('data.invalid', 1);

        $this->getJson('/api/v1/transfers/not-a-model/fields')->assertNotFound();
    }
}
