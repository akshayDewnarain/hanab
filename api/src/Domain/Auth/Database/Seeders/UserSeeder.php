<?php

namespace Domain\Auth\Database\Seeders;

use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Employees\Models\Employee;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        if (! User::role(BootstrapRoleNames::SUPER_ADMIN)->exists()) {
            $superAdmin = User::query()->firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Super Admin',
                    'password' => 'HanabDemo2026',
                    'email_verified_at' => now(),
                    'status' => UserStatus::Active,
                    'password_set_at' => now(),
                ],
            );
            $superAdmin->assignRole(BootstrapRoleNames::SUPER_ADMIN);
        }

        $this->account(
            'admin@hanab.test',
            'admin',
            Employee::query()
                ->where('is_active', true)
                ->whereHas('employeeRole', fn ($query) => $query->where('name', 'Directeur'))
                ->orderBy('id')
                ->first(),
        );

        $this->account(
            'medewerker@hanab.test',
            'employee',
            Employee::query()
                ->where('is_active', true)
                ->whereHas('employeeRole', fn ($query) => $query->where('name', 'Monteur'))
                ->orderBy('id')
                ->first(),
        );
    }

    private function account(string $email, string $role, ?Employee $employee): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $employee === null ? $email : trim($employee->first_name.' '.$employee->last_name),
                'password' => 'HanabDemo2026',
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'password_set_at' => now(),
                'employee_id' => $employee?->id,
                'is_internal_account' => false,
            ],
        );

        if ($employee !== null && $user->employee_id === null && ! User::query()->where('employee_id', $employee->id)->exists()) {
            $user->employee_id = $employee->id;
            $user->name = trim($employee->first_name.' '.$employee->last_name);
            $user->save();
        }

        if ($user->password_set_at === null) {
            $user->password_set_at = now();
            $user->save();
        }

        $user->assignRole($role);
    }
}
