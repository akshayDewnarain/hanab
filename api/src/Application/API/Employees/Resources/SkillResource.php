<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\SkillResource as DomainSkillResource;
use Illuminate\Http\Request;

class SkillResource extends DomainSkillResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
