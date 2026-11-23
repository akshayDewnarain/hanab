<?php

use Domain\Auth\Models\Role;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Employees\Models\Skill;
use Domain\Messages\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard');

        $names = [
            'import employees',
            'publish messages',
        ];

        foreach ([
            User::class,
            Role::class,
            Employee::class,
            EmployeeCertificate::class,
            EmployeeSkill::class,
            Certificate::class,
            Skill::class,
            EmployeeLocation::class,
            EmployeeRole::class,
            Message::class,
        ] as $modelClass) {
            array_push($names, ...PermissionService::crudPermissions($modelClass));
        }

        foreach ($names as $name) {
            Permission::findOrCreate($name, $guard);
        }

        /** @var Role $role */
        $role = Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, $guard);
        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
