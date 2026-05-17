<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\EmployeeRoleResource as DomainEmployeeRoleResource;
use Illuminate\Http\Request;

class EmployeeRoleResource extends DomainEmployeeRoleResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
