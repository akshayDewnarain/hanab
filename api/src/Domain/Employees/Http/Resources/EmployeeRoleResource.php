<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read EmployeeRole $resource */
abstract class EmployeeRoleResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'description',
            'is_active',
        ]);
    }
}
