<?php

namespace Database\Seeders;

use Domain\Auth\Models\Role;
use Domain\Auth\Support\ReservedContentPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DemoAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');
        $reader = [
            'view employees',
            'view employee_skills',
            'view employee_certificates',
            'view certificates',
            'view skills',
            'view employee_locations',
            'view employee_roles',
            'view organization_levels',
            'view messages',
        ];

        $employee = Role::query()->firstOrCreate([
            'name' => 'employee',
            'guard_name' => $guard,
        ]);
        $employee->syncPermissions($reader);

        $legacy = Role::query()->where('name', 'Medewerker')->where('guard_name', $guard)->first();
        if ($legacy instanceof Role) {
            $legacy->givePermissionTo($reader);
        }

        $operational = ReservedContentPermissions::except(
            Permission::query()->where('guard_name', $guard)->pluck('name')->all(),
        );
        $admin = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);
        $admin->syncPermissions($operational);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
