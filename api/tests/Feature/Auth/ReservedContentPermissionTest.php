<?php

namespace Tests\Feature\Auth;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReservedContentPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_administrator_cannot_grant_or_revoke_help_and_knowledge_permissions(): void
    {
        foreach ([
            'view roles',
            'create roles',
            'update roles',
            'view employees',
            'view help articles',
            'create help articles',
            'update help articles',
            'delete help articles',
            'publish help articles',
            'view knowledge articles',
            'delete knowledge articles',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $client = User::factory()->create();
        $client->givePermissionTo(['view roles', 'create roles', 'update roles']);
        $this->actingAs($client);

        $listed = array_column($this->getJson('/api/v1/permissions')->assertOk()->json('data'), 'name');
        $this->assertContains('view employees', $listed);
        $this->assertNotContains('create help articles', $listed);
        $this->assertNotContains('view knowledge articles', $listed);
        $this->assertNotContains('publish help articles', $listed);

        $this->postJson('/api/v1/roles', [
            'name' => 'Editors',
            'permissions' => ['view employees', 'create help articles'],
        ])->assertStatus(422);
        $this->assertNull(Role::query()->where('name', 'Editors')->first());

        $role = Role::findOrCreate('editors', 'web');
        $role->syncPermissions(['view employees', 'update help articles']);

        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => 'editors',
            'permissions' => ['view employees', 'delete knowledge articles'],
        ])->assertStatus(422);
        $this->assertTrue($role->fresh()->hasPermissionTo('update help articles'));
        $this->assertFalse($role->fresh()->hasPermissionTo('delete knowledge articles'));

        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => 'editors',
            'permissions' => ['view employees'],
        ])->assertOk();
        $fresh = $role->fresh();
        $this->assertTrue($fresh->hasPermissionTo('view employees'));
        $this->assertTrue($fresh->hasPermissionTo('update help articles'));

        $shown = array_column($this->getJson('/api/v1/roles/'.$role->id)->assertOk()->json('data.permissions'), 'name');
        $this->assertContains('view employees', $shown);
        $this->assertNotContains('update help articles', $shown);
    }

    public function test_a_super_admin_can_assign_and_remove_help_and_knowledge_permissions(): void
    {
        Permission::findOrCreate('create help articles', 'web');
        Permission::findOrCreate('view employees', 'web');
        $this->actingAsSuperAdmin();

        $listed = array_column($this->getJson('/api/v1/permissions')->assertOk()->json('data'), 'name');
        $this->assertContains('create help articles', $listed);

        $this->postJson('/api/v1/roles', [
            'name' => 'Help editors',
            'permissions' => ['create help articles'],
        ])->assertCreated();

        $role = Role::query()->where('name', 'Help editors')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('create help articles'));
        $shown = array_column($this->getJson('/api/v1/roles/'.$role->id)->assertOk()->json('data.permissions'), 'name');
        $this->assertContains('create help articles', $shown);

        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => 'Help editors',
            'permissions' => ['view employees'],
        ])->assertOk();
        $this->assertFalse($role->fresh()->hasPermissionTo('create help articles'));
        $this->assertTrue($role->fresh()->hasPermissionTo('view employees'));
    }
}
