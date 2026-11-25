<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $skills = [
            ['name' => 'Gasdistributie', 'code' => 'GAS', 'category' => 'technical', 'label_background_color' => '#E0F2FE', 'label_text_color' => '#0C4A6E', 'label_border_color' => '#7DD3FC', 'description' => 'Aanleg en onderhoud van gasdistributie.'],
            ['name' => 'Laagspanning', 'code' => 'LS', 'category' => 'technical', 'label_background_color' => '#FEF3C7', 'label_text_color' => '#78350F', 'label_border_color' => '#FCD34D', 'description' => 'Werkzaamheden aan laagspanningsinstallaties.'],
            ['name' => 'Hoogspanning', 'code' => 'HS', 'category' => 'technical', 'label_background_color' => '#FFEDD5', 'label_text_color' => '#7C2D12', 'label_border_color' => '#FDBA74', 'description' => 'Werkzaamheden aan hoogspanningsinstallaties.'],
            ['name' => 'Warmtenetten', 'code' => 'WARM', 'category' => 'domain', 'label_background_color' => '#FEE2E2', 'label_text_color' => '#7F1D1D', 'label_border_color' => '#FCA5A5', 'description' => 'Aanleg en beheer van warmtenetten.'],
            ['name' => 'Werkvoorbereiding', 'code' => 'WV', 'category' => 'domain', 'label_background_color' => '#E0E7FF', 'label_text_color' => '#312E81', 'label_border_color' => '#A5B4FC', 'description' => 'Voorbereiden van uitvoering en planning.'],
            ['name' => 'Klantcontact', 'code' => 'KLANT', 'category' => 'soft_skill', 'label_background_color' => '#FCE7F3', 'label_text_color' => '#831843', 'label_border_color' => '#F9A8D4', 'description' => 'Afstemming met opdrachtgevers en bewoners.'],
            ['name' => 'Samenwerken', 'code' => 'TEAM', 'category' => 'soft_skill', 'label_background_color' => '#DCFCE7', 'label_text_color' => '#14532D', 'label_border_color' => '#86EFAC', 'description' => 'Werken in uitvoeringsteams.'],
            ['name' => 'Leidinggeven', 'code' => 'LEAD', 'category' => 'soft_skill', 'label_background_color' => '#CCFBF1', 'label_text_color' => '#134E4A', 'label_border_color' => '#5EEAD4', 'description' => 'Aansturen van teams en projecten.'],
        ];

        foreach ($skills as $skill) {
            $record = Skill::query()->firstOrNew(['name' => $skill['name']]);
            $record->fill([
                'category' => $skill['category'],
                'description' => $skill['description'],
                'label_background_color' => $skill['label_background_color'],
                'label_text_color' => $skill['label_text_color'],
                'label_border_color' => $skill['label_border_color'],
                'is_active' => true,
            ]);
            $codeTaken = Skill::query()
                ->where('code', $skill['code'])
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record->id))
                ->exists();
            if (! $codeTaken) {
                $record->code = $skill['code'];
            }
            $record->save();
        }
    }
}
