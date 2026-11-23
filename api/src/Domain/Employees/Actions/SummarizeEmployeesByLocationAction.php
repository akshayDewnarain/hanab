<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Actions\BaseAction;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

class SummarizeEmployeesByLocationAction extends BaseAction
{
    /**
     * Active work locations that have coordinates, with a count from the same employee query as the directory.
     *
     * @return list<array{id: int, name: string, latitude: float, longitude: float, employee_count: int}>
     */
    public function execute(BaseQueryBuilder $employees): array
    {
        $builder = $employees->getEloquentBuilder()->clone();
        $builder->getQuery()->columns = null;
        $builder->reorder();

        $counts = $builder
            ->selectRaw('employees.employee_location_id as location_id, COUNT(*) as employee_count')
            ->whereNotNull('employees.employee_location_id')
            ->groupBy('employees.employee_location_id')
            ->pluck('employee_count', 'location_id');

        return EmployeeLocation::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get()
            ->map(fn (EmployeeLocation $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'employee_count' => (int) ($counts[$location->id] ?? $counts[(string) $location->id] ?? 0),
            ])
            ->all();
    }
}
