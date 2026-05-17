<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        Employee::factory()
            ->count(8)
            ->create();
    }
}
