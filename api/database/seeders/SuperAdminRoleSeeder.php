<?php

namespace Database\Seeders;

use Domain\Auth\Models\Role;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SuperAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard');

        /** @var Role $role */
        $role = Role::query()->firstOrCreate(
            [
                'name' => BootstrapRoleNames::SUPER_ADMIN,
                'guard_name' => $guard,
            ],
        );

        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->pluck('name')->all());
    }
}
