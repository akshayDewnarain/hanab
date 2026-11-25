<?php

namespace Domain\Dashboard\Services;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Dashboard\DataTransferObjects\DashboardAction;
use Domain\Employees\Models\EmployeeLocation;
use Illuminate\Database\Eloquent\Builder;

class WorkLocationActionProvider implements DashboardActionProvider
{
    public function provide(User $user): array
    {
        if (! Maintainer::allows($user, PermissionService::updatePermission(EmployeeLocation::class))) {
            return [];
        }

        $unmapped = EmployeeLocation::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('latitude')->orWhereNull('longitude'))
            ->count();

        return [
            new DashboardAction(
                key: 'locations_without_coordinates',
                titleKey: 'DASHBOARD_TASK_MAP',
                descriptionKey: 'DASHBOARD_TASK_MAP_HINT',
                icon: 'material-symbols:map-outline',
                count: $unmapped,
                priority: 'low',
                route: 'locations',
                query: [],
            ),
        ];
    }
}
