<?php

namespace Domain\Auth\Database\Seeders;

use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(BootstrapRoleNames::SUPER_ADMIN);
    }
}
