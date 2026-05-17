<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\Employee;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read Employee $resource */
abstract class EmployeeResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'employee_role_id',
            'employee_location_id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'employee_number',
            'employment_type',
            'start_date',
            'is_active',
        ]);
    }
}
