<?php

namespace Domain\Dashboard\Services;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Dashboard\DataTransferObjects\DashboardAction;
use Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data-quality actions for people who can maintain employees.
 * A missing manager is not an action: roots of the organogram are valid.
 */
class EmployeeDataQualityActionProvider implements DashboardActionProvider
{
    public function provide(User $user): array
    {
        if (! Maintainer::allows($user, PermissionService::updatePermission(Employee::class))) {
            return [];
        }

        $active = Employee::query()->where('is_active', true);

        return [
            $this->action(
                'missing_skills',
                'DASHBOARD_TASK_SKILLS',
                'DASHBOARD_TASK_SKILLS_HINT',
                'material-symbols:verified-outline-rounded',
                (clone $active)->whereDoesntHave('skills')->count(),
                ['filter[missing_skill]' => '1', 'filter[is_active]' => '1'],
            ),
            $this->action(
                'missing_certificates',
                'DASHBOARD_TASK_CERTIFICATES',
                'DASHBOARD_TASK_CERTIFICATES_HINT',
                'material-symbols:workspace-premium-outline-rounded',
                (clone $active)->whereDoesntHave('certificates')->count(),
                ['filter[missing_certificate]' => '1', 'filter[is_active]' => '1'],
            ),
            $this->action(
                'missing_business_phone',
                'DASHBOARD_TASK_PHONE',
                'DASHBOARD_TASK_PHONE_HINT',
                'material-symbols:call-outline-rounded',
                (clone $active)->where(fn (Builder $query) => $query->whereNull('business_phone')->orWhere('business_phone', ''))->count(),
                ['filter[missing_business_phone]' => '1', 'filter[is_active]' => '1'],
            ),
            $this->action(
                'missing_business_email',
                'DASHBOARD_TASK_EMAIL',
                'DASHBOARD_TASK_EMAIL_HINT',
                'material-symbols:mail-outline-rounded',
                (clone $active)->where(fn (Builder $query) => $query->whereNull('business_email')->orWhere('business_email', ''))->count(),
                ['filter[missing_business_email]' => '1', 'filter[is_active]' => '1'],
            ),
            $this->action(
                'missing_location',
                'DASHBOARD_TASK_LOCATION',
                'DASHBOARD_TASK_LOCATION_HINT',
                'material-symbols:location-on-outline-rounded',
                (clone $active)->whereNull('employee_location_id')->count(),
                ['filter[missing_location]' => '1', 'filter[is_active]' => '1'],
            ),
        ];
    }

    /**
     * @param  array<string, string>  $query
     */
    private function action(string $key, string $titleKey, string $descriptionKey, string $icon, int $count, array $query): DashboardAction
    {
        return new DashboardAction(
            key: $key,
            titleKey: $titleKey,
            descriptionKey: $descriptionKey,
            icon: $icon,
            count: $count,
            priority: 'normal',
            route: 'employees',
            query: $query,
        );
    }
}
