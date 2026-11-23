<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\ExpertiseSearchResultResource as DomainExpertiseSearchResultResource;
use Illuminate\Http\Request;

class ExpertiseSearchResultResource extends DomainExpertiseSearchResultResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
