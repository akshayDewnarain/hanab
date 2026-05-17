<?php

namespace Database\Seeders;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Employees\Models\Skill;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard');

        $models = [
            User::class,
            Role::class,
            Employee::class,
            EmployeeCertificate::class,
            EmployeeSkill::class,
            Certificate::class,
            Skill::class,
            EmployeeLocation::class,
            EmployeeRole::class,
        ];

        foreach ($models as $modelClass) {
            foreach (PermissionService::crudPermissions($modelClass) as $permissionName) {
                Permission::findOrCreate($permissionName, $guard);
            }
        }
    }
}
