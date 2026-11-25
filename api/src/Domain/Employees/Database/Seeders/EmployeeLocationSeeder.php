<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\EmployeeLocation;
use Illuminate\Database\Seeder;

class EmployeeLocationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $offices = [
            ['name' => 'Utrecht', 'cluster_name' => 'Midden', 'latitude' => 52.090737, 'longitude' => 5.12142],
            ['name' => 'Amsterdam', 'cluster_name' => 'Noord', 'latitude' => 52.367573, 'longitude' => 4.904139],
            ['name' => 'Rotterdam', 'cluster_name' => 'Zuid', 'latitude' => 51.9225, 'longitude' => 4.47917],
            ['name' => 'Den Haag', 'cluster_name' => 'West', 'latitude' => 52.070498, 'longitude' => 4.3007],
            ['name' => 'Eindhoven', 'cluster_name' => 'Zuid', 'latitude' => 51.441642, 'longitude' => 5.469722],
            ['name' => 'Groningen', 'cluster_name' => 'Noord', 'latitude' => 53.219383, 'longitude' => 6.566502],
            ['name' => 'Zwolle', 'cluster_name' => 'Oost', 'latitude' => 52.516775, 'longitude' => 6.083022],
        ];

        foreach ($offices as $office) {
            $location = EmployeeLocation::query()->firstOrNew(['name' => $office['name']]);
            $location->cluster_name = $location->cluster_name ?: $office['cluster_name'];
            $location->is_active = true;
            if ($location->latitude === null || $location->longitude === null) {
                $location->latitude = $office['latitude'];
                $location->longitude = $office['longitude'];
            }
            $location->save();
        }
    }
}
