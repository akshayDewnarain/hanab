<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\Certificate;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $certificates = [
            ['name' => 'VCA Basis', 'code' => 'VCA-B', 'category' => 'safety', 'requires_expiry_date' => true, 'label_background_color' => '#FEE2E2', 'label_text_color' => '#7F1D1D', 'label_border_color' => '#FCA5A5', 'description' => 'Basisveiligheid voor operationele medewerkers.'],
            ['name' => 'VCA VOL', 'code' => 'VCA-V', 'category' => 'safety', 'requires_expiry_date' => true, 'label_background_color' => '#FFE4E6', 'label_text_color' => '#881337', 'label_border_color' => '#FDA4AF', 'description' => 'Veiligheid voor operationeel leidinggevenden.'],
            ['name' => 'VIAG', 'code' => 'VIAG', 'category' => 'technical', 'requires_expiry_date' => true, 'label_background_color' => '#E0F2FE', 'label_text_color' => '#0C4A6E', 'label_border_color' => '#7DD3FC', 'description' => 'Veiligheidsinstructie aardgas.'],
            ['name' => 'BEI-LS', 'code' => 'BEI-LS', 'category' => 'technical', 'requires_expiry_date' => true, 'label_background_color' => '#FEF3C7', 'label_text_color' => '#78350F', 'label_border_color' => '#FCD34D', 'description' => 'Bedrijfsvoering elektrische installaties, laagspanning.'],
            ['name' => 'NEN 3140', 'code' => 'NEN3140', 'category' => 'compliance', 'requires_expiry_date' => true, 'label_background_color' => '#E0E7FF', 'label_text_color' => '#312E81', 'label_border_color' => '#A5B4FC', 'description' => 'Vakbekwaam persoon voor laagspanningsinstallaties.'],
        ];

        foreach ($certificates as $certificate) {
            $record = Certificate::query()->firstOrNew(['name' => $certificate['name']]);
            $record->fill([
                'category' => $certificate['category'],
                'description' => $certificate['description'],
                'requires_expiry_date' => $certificate['requires_expiry_date'],
                'label_background_color' => $certificate['label_background_color'],
                'label_text_color' => $certificate['label_text_color'],
                'label_border_color' => $certificate['label_border_color'],
                'is_active' => true,
            ]);
            $codeTaken = Certificate::query()
                ->where('code', $certificate['code'])
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record->id))
                ->exists();
            if (! $codeTaken) {
                $record->code = $certificate['code'];
            }
            $record->save();
        }
    }
}
