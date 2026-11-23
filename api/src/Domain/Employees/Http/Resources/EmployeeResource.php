<?php

namespace Domain\Employees\Http\Resources;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Employees\Models\Employee;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read Employee $resource */
abstract class EmployeeResource extends BaseResource
{
    public function toArray(Request $request)
    {
        $attributes = [
            'employee_role_id',
            'employee_location_id',
            'manager_id',
            'organization_level_id',
            'first_name',
            'last_name',
            'initials',
            'email',
            'phone',
            'business_email',
            'business_phone',
            'introduction',
            'employee_number',
            'employment_type',
            'start_date',
            'is_active',
        ];

        $user = $request->user();
        if ($user instanceof User && Maintainer::allows($user, PermissionService::updatePermission(Employee::class))) {
            $attributes[] = 'date_of_birth';
        }

        return $this->withAttributes($attributes);
    }
}
