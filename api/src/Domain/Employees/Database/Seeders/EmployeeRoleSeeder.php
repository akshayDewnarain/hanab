<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\OrganizationLevel;
use Illuminate\Database\Seeder;

class EmployeeRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $levels = OrganizationLevel::query()->pluck('id', 'name');
        $roles = [
            'Directeur' => ['parent' => null, 'level' => 'Executive', 'sort_order' => 10, 'description' => 'Eindverantwoordelijk voor de organisatie.'],
            'Regiodirecteur' => ['parent' => 'Directeur', 'level' => 'Senior Management', 'sort_order' => 20, 'description' => 'Verantwoordelijk voor een regio.'],
            'Hoofd uitvoerder' => ['parent' => 'Regiodirecteur', 'level' => 'Management', 'sort_order' => 30, 'description' => 'Stuurt de uitvoering in een vestiging aan.'],
            'Uitvoerder' => ['parent' => 'Hoofd uitvoerder', 'level' => 'Team Lead', 'sort_order' => 40, 'description' => 'Leidt een uitvoeringsteam op locatie.'],
            'Planner' => ['parent' => 'Regiodirecteur', 'level' => 'Management', 'sort_order' => 35, 'description' => 'Plant mensen, materiaal en werkzaamheden.'],
            'Project Manager' => ['parent' => 'Regiodirecteur', 'level' => 'Management', 'sort_order' => 36, 'description' => 'Bewaakt projecten van opdracht tot oplevering.'],
            'Calculator' => ['parent' => 'Planner', 'level' => 'Employee', 'sort_order' => 50, 'description' => 'Stelt kostprijzen en offertes op.'],
            'Administrateur' => ['parent' => 'Directeur', 'level' => 'Employee', 'sort_order' => 55, 'description' => 'Ondersteunt administratie en dossiervorming.'],
            'Monteur' => ['parent' => 'Uitvoerder', 'level' => 'Employee', 'sort_order' => 60, 'description' => 'Voert werkzaamheden aan de infrastructuur uit.'],
        ];

        foreach ($roles as $name => $role) {
            EmployeeRole::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $role['description'],
                    'is_active' => true,
                    'sort_order' => $role['sort_order'],
                    'organization_level_id' => $levels[$role['level']] ?? null,
                ],
            );
        }

        foreach ($roles as $name => $role) {
            $record = EmployeeRole::query()->where('name', $name)->first();
            $parentId = $role['parent'] === null
                ? null
                : EmployeeRole::query()->where('name', $role['parent'])->value('id');
            if ($record !== null && $record->parent_id !== $parentId) {
                $record->parent_id = $parentId;
                $record->save();
            }
        }

        $offices = EmployeeLocation::query()->pluck('name');
        EmployeeRole::query()
            ->whereIn('name', $offices)
            ->whereDoesntHave('employees')
            ->update(['is_active' => false]);

        EmployeeRole::query()
            ->where('name', 'Project Lead')
            ->whereDoesntHave('employees')
            ->update(['is_active' => false]);
    }
}
