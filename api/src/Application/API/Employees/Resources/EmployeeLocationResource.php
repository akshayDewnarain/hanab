<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\EmployeeLocationResource as DomainEmployeeLocationResource;
use Illuminate\Http\Request;

class EmployeeLocationResource extends DomainEmployeeLocationResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
