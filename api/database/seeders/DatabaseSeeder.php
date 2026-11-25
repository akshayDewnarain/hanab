<?php

namespace Database\Seeders;

use Domain\Auth\Database\Seeders\UserSeeder;
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
            PermissionSeeder::class,
            SuperAdminRoleSeeder::class,
            EmployeeLocationSeeder::class,
            SkillSeeder::class,
            CertificateSeeder::class,
            EmployeeRoleSeeder::class,
            EmployeeSeeder::class,
            DemoAccessSeeder::class,
            UserSeeder::class,
            HelpGuideSeeder::class,
        ]);
    }
}
