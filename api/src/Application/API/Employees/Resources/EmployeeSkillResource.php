<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\EmployeeSkillResource as DomainEmployeeSkillResource;
use Illuminate\Http\Request;

class EmployeeSkillResource extends DomainEmployeeSkillResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
