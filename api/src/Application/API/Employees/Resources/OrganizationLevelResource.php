<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\OrganizationLevelResource as DomainOrganizationLevelResource;
use Illuminate\Http\Request;

class OrganizationLevelResource extends DomainOrganizationLevelResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
