<?php

namespace Domain\Employees\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        if (DB::table('skills')->count() > 0) {
            return;
        }

        DB::table('skills')->insert([
            [
                'name' => 'Gas Infrastructure',
                'code' => 'GAS',
                'label_background_color' => '#E0F2FE',
                'label_text_color' => '#0C4A6E',
                'label_border_color' => '#7DD3FC',
                'category' => 'technical',
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fiber Networks',
                'code' => 'FIB',
                'label_background_color' => '#EDE9FE',
                'label_text_color' => '#4C1D95',
                'label_border_color' => '#C4B5FD',
                'category' => 'technical',
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Leadership',
                'code' => 'LEAD',
                'label_background_color' => '#DCFCE7',
                'label_text_color' => '#14532D',
                'label_border_color' => '#86EFAC',
                'category' => 'soft_skill',
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
