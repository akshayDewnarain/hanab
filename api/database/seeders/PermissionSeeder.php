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
use Domain\Employees\Models\OrganizationLevel;
use Domain\Employees\Models\Skill;
use Domain\Auth\Models\UserInvitation;
use Domain\Messages\Models\Message;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

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
            OrganizationLevel::class,
            Message::class,
            UserInvitation::class,
        ];

        $extra = [
            'import employees',
            'publish messages',
            'view knowledge articles',
            'create knowledge articles',
            'update knowledge articles',
            'delete knowledge articles',
            'publish knowledge articles',
            'manage knowledge categories',
            'manage knowledge attachments',
            'view knowledge revisions',
            'restore knowledge revisions',
            'view help articles',
            'create help articles',
            'update help articles',
            'delete help articles',
            'publish help articles',
            'manage help categories',
            'view help revisions',
            'restore help revisions',
        ];

        foreach ($models as $modelClass) {
            foreach (PermissionService::crudPermissions($modelClass) as $permissionName) {
                $this->ensurePermission($permissionName, $guard);
            }
        }

        foreach ($extra as $permissionName) {
            $this->ensurePermission($permissionName, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermission(string $name, string $guard): void
    {
        Permission::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => $guard,
        ]);
    }
}
