<?php

namespace Database\Seeders;

use Domain\Employees\Database\Seeders\CertificateSeeder;
use Domain\Employees\Database\Seeders\EmployeeLocationSeeder;
use Domain\Employees\Database\Seeders\EmployeeRoleSeeder;
use Domain\Employees\Database\Seeders\EmployeeSeeder;
use Domain\Employees\Database\Seeders\SkillSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EmployeeRoleSeeder::class,
            EmployeeLocationSeeder::class,
            SkillSeeder::class,
            CertificateSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
