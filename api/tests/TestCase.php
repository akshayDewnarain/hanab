<?php

namespace Tests;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsSuperAdmin(): User
    {
        $role = Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsColleague(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }
}
