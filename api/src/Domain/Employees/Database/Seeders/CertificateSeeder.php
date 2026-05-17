<?php

namespace Domain\Employees\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        if (DB::table('certificates')->count() > 0) {
            return;
        }

        DB::table('certificates')->insert([
            [
                'name' => 'VCA',
                'code' => 'VCA',
                'label_background_color' => '#FEE2E2',
                'label_text_color' => '#7F1D1D',
                'label_border_color' => '#FCA5A5',
                'category' => 'safety',
                'description' => null,
                'requires_expiry_date' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SCIOS',
                'code' => 'SCIOS',
                'label_background_color' => '#E0E7FF',
                'label_text_color' => '#312E81',
                'label_border_color' => '#A5B4FC',
                'category' => 'technical',
                'description' => null,
                'requires_expiry_date' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
