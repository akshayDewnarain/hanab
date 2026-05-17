<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read EmployeeLocation $resource */
abstract class EmployeeLocationResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'cluster_name',
            'is_active',
        ]);
    }
}
