<?php

namespace Tests\Feature\Support;

use Domain\Auth\Models\User;
use Domain\Employees\Models\EmployeeLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LocationImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperAdmin();
    }

    public function test_upsert_creates_updates_and_leaves_an_unchanged_location(): void
    {
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'cluster_name' => 'Midden',
            'is_active' => true,
        ]);
        EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'cluster_name' => 'West',
            'is_active' => true,
            'latitude' => 1,
            'longitude' => 5,
        ]);

        $preview = $this->postJson('/api/v1/transfers/employee-locations/preview', [
            'strategy' => 'upsert',
            'rows' => [
                $this->place('Utrecht', 'Midden', '52.090737', '5.121420'),
                $this->place('Zwolle', 'Oost', '52.516775', '6.083022'),
                $this->place('Utrecht', 'West', '1', '5'),
            ],
        ]);

        $preview->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.unchanged', 1)
            ->assertJsonPath('data.rows.0.changes.0.field', 'latitude')
            ->assertJsonPath('data.rows.0.changes.0.from', '')
            ->assertJsonPath('data.rows.0.changes.0.to', '52.090737');
        $this->assertSame(2, EmployeeLocation::query()->count());
        $this->assertNull($utrecht->refresh()->latitude);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'policy' => 'strict',
            'rows' => [
                $this->place('Utrecht', 'Midden', '52.090737', '5.121420'),
                $this->place('Zwolle', 'Oost', '52.516775', '6.083022'),
            ],
        ])->assertOk()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 1);

        $this->assertSame(3, EmployeeLocation::query()->count());
        $this->assertEqualsWithDelta(52.090737, (float) $utrecht->refresh()->latitude, 0.000001);
        $this->assertEquals(1.0, (float) EmployeeLocation::query()->where('cluster_name', 'West')->value('latitude'));
    }

    public function test_create_only_skips_existing_rows_and_update_only_skips_unknown_rows(): void
    {
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'cluster_name' => 'Midden',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'create_only',
            'rows' => [$this->place('Utrecht', 'Midden', '52.090737', '5.121420', 'false')],
        ])->assertOk()->assertJsonPath('data.skipped', 1)->assertJsonPath('data.updated', 0);

        $this->assertTrue($utrecht->refresh()->is_active);
        $this->assertNull($utrecht->latitude);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'update_only',
            'rows' => [$this->place('Zwolle', 'Oost', '52.5', '6.1')],
        ])->assertOk()->assertJsonPath('data.skipped', 1)->assertJsonPath('data.created', 0);

        $this->assertNull(EmployeeLocation::query()->where('name', 'Zwolle')->first());
    }

    public function test_ambiguous_matches_and_duplicate_file_rows_are_errors(): void
    {
        $first = EmployeeLocation::factory()->create(['name' => 'Utrecht', 'cluster_name' => 'Midden', 'is_active' => true]);
        $second = EmployeeLocation::factory()->create(['name' => 'utrecht', 'cluster_name' => 'midden', 'is_active' => true]);

        $ambiguous = $this->postJson('/api/v1/transfers/employee-locations/preview', [
            'strategy' => 'upsert',
            'rows' => [$this->place('Utrecht', 'Midden', '52.1', '5.1')],
        ]);
        $ambiguous->assertOk()->assertJsonPath('data.invalid', 1)->assertJsonPath('data.errors.0.code', 'ambiguous');

        $missingCluster = $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => [[
                'name' => 'Utrecht',
                'is_active' => 'true',
                'latitude' => '52.1',
                'longitude' => '5.1',
            ]],
        ]);
        $missingCluster->assertStatus(422)->assertJsonPath('data.saved', false);
        $this->assertNull($first->refresh()->latitude);
        $this->assertNull($second->refresh()->latitude);

        EmployeeLocation::query()->delete();
        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => [
                $this->place('Zwolle', 'Oost', '52.5', '6.1'),
                $this->place('Zwolle', 'Oost', '52.6', '6.2'),
            ],
        ])->assertStatus(422)->assertJsonPath('data.errors.0.code', 'duplicate');
        $this->assertSame(0, EmployeeLocation::query()->count());
    }

    public function test_invalid_coordinates_are_rejected_and_a_strict_batch_writes_nothing(): void
    {
        $before = EmployeeLocation::query()->count();

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'policy' => 'strict',
            'rows' => [
                $this->place('Zwolle', 'Oost', '52.5', '6.1'),
                $this->place('Utrecht', 'Midden', '95', '5.1'),
            ],
        ])->assertStatus(422)->assertJsonPath('data.saved', false)->assertJsonPath('data.created', 0);

        $this->assertSame($before, EmployeeLocation::query()->count());
    }

    public function test_partial_import_commits_only_the_valid_row_after_confirmation(): void
    {
        $rows = [
            $this->place('Zwolle', 'Oost', '52.5', '6.1'),
            $this->place('Utrecht', 'Midden', '95', '5.1'),
        ];

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'policy' => 'valid_rows',
            'rows' => $rows,
        ])->assertStatus(422)->assertJsonPath('data.saved', false);
        $this->assertSame(0, EmployeeLocation::query()->count());

        $saved = $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'policy' => 'valid_rows',
            'confirm_partial' => true,
            'rows' => $rows,
        ]);

        $saved->assertOk()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.errors.0.code', 'invalid')
            ->assertJsonPath('data.errors.0.field', 'latitude');

        $this->assertSame(1, EmployeeLocation::query()->count());
        $this->assertSame('Zwolle', EmployeeLocation::query()->value('name'));
    }

    public function test_an_update_preserves_unmapped_and_empty_fields_and_a_null_clears_coordinates(): void
    {
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'cluster_name' => 'Midden',
            'is_active' => true,
            'latitude' => 52.09,
            'longitude' => 5.12,
        ]);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => [[
                'name' => 'Utrecht',
                'cluster_name' => 'Midden',
                'is_active' => 'false',
                'latitude' => '',
            ]],
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $utrecht->refresh();
        $this->assertFalse($utrecht->is_active);
        $this->assertEqualsWithDelta(52.09, (float) $utrecht->latitude, 0.000001);
        $this->assertEqualsWithDelta(5.12, (float) $utrecht->longitude, 0.000001);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => [[
                'name' => 'Utrecht',
                'cluster_name' => 'Midden',
                'latitude' => 'null',
                'longitude' => 'null',
            ]],
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertNull($utrecht->refresh()->latitude);
        $this->assertNull($utrecht->longitude);
        $this->assertFalse($utrecht->is_active);
    }

    public function test_a_user_without_update_permission_cannot_update_and_a_stale_file_is_rechecked(): void
    {
        $utrecht = EmployeeLocation::factory()->create([
            'name' => 'Utrecht',
            'cluster_name' => 'Midden',
            'is_active' => true,
        ]);
        $rows = [
            $this->place('Utrecht', 'Midden', '52.090737', '5.121420'),
            $this->place('Zwolle', 'Oost', '52.5', '6.1'),
        ];

        $permission = Permission::findOrCreate('create employee_locations', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'policy' => 'strict',
            'rows' => $rows,
        ])->assertStatus(422)->assertJsonPath('data.saved', false)->assertJsonPath('data.errors.0.code', 'unauthorized');
        $this->assertNull($utrecht->refresh()->latitude);
        $this->assertNull(EmployeeLocation::query()->where('name', 'Zwolle')->first());

        $this->actingAsSuperAdmin();
        $preview = $this->postJson('/api/v1/transfers/employee-locations/preview', [
            'strategy' => 'upsert',
            'rows' => [$this->place('Den Haag', 'West', '52.07', '4.3')],
        ])->assertOk()->assertJsonPath('data.created', 1);

        EmployeeLocation::factory()->create([
            'name' => 'Den Haag',
            'cluster_name' => 'West',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => [$this->place('Den Haag', 'West', '52.07', '4.3')],
        ])->assertOk()->assertJsonPath('data.created', 0)->assertJsonPath('data.updated', 1);

        $this->assertSame(1, EmployeeLocation::query()->where('name', 'Den Haag')->count());
        $this->assertEqualsWithDelta(52.07, (float) EmployeeLocation::query()->where('name', 'Den Haag')->value('latitude'), 0.000001);
        $this->assertSame(1, $preview->json('data.created'));
    }

    public function test_a_second_commit_does_not_create_the_location_again(): void
    {
        $rows = [$this->place('Zwolle', 'Oost', '52.5', '6.1')];

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => $rows,
        ])->assertOk()->assertJsonPath('data.created', 1);

        $this->postJson('/api/v1/transfers/employee-locations/commit', [
            'strategy' => 'upsert',
            'rows' => $rows,
        ])->assertOk()->assertJsonPath('data.created', 0)->assertJsonPath('data.unchanged', 1);

        $this->assertSame(1, EmployeeLocation::query()->count());
    }

    /**
     * @return array<string, string>
     */
    private function place(string $name, string $cluster, string $latitude, string $longitude, string $active = 'true'): array
    {
        return [
            'name' => $name,
            'cluster_name' => $cluster,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_active' => $active,
        ];
    }
}
