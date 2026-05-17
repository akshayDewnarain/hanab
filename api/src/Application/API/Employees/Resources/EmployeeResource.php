<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\EmployeeResource as DomainEmployeeResource;
use Illuminate\Http\Request;

class EmployeeResource extends DomainEmployeeResource
{
    public function toArray(Request $request): array
    {
        $media = $this->resource->getFirstMedia('image');

        return [
            ...parent::toArray($request),
            'image' => [
                'thumb_url' => $media?->getUrl('thumb'),
                'url' => $media?->getUrl(),
            ],
            'employee_role' => $this->whenLoaded(
                'employeeRole',
                fn () => [
                    'id' => $this->resource->employeeRole?->id,
                    'name' => $this->resource->employeeRole?->name,
                ],
            ),
            'employee_location' => $this->whenLoaded(
                'employeeLocation',
                fn () => [
                    'id' => $this->resource->employeeLocation?->id,
                    'name' => $this->resource->employeeLocation?->name,
                ],
            ),
            'skills' => $this->whenLoaded('skills', fn () => SkillResource::collection($this->resource->skills)),
            'certificates' => $this->whenLoaded(
                'certificates',
                fn () => CertificateResource::collection($this->resource->certificates),
            ),
        ];
    }
}
