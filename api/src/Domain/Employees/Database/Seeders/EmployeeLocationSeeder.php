<?php

namespace Domain\Employees\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeLocationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        if (DB::table('employee_locations')->count() > 0) {
            return;
        }

        DB::table('employee_locations')->insert([
            [
                'name' => 'Amsterdam',
                'cluster_name' => 'Cluster North',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rotterdam',
                'cluster_name' => 'Cluster South',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
