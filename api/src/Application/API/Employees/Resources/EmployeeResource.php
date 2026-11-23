<?php

namespace Application\API\Employees\Resources;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Employees\Http\Resources\EmployeeResource as DomainEmployeeResource;
use Domain\Employees\Models\Employee;
use Domain\Employees\Support\ExpertiseExplanation;
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
            'manager' => $this->whenLoaded('manager', function () {
                $manager = $this->resource->manager;
                $user = request()->user();
                if (
                    $manager === null
                    || (! $manager->is_active && ! Maintainer::allows(
                        $user instanceof User ? $user : null,
                        PermissionService::updatePermission(Employee::class),
                    ))
                ) {
                    return null;
                }

                return [
                    'id' => $manager->id,
                    'first_name' => $manager->first_name,
                    'last_name' => $manager->last_name,
                    'initials' => $manager->initials,
                    'role' => $manager->relationLoaded('employeeRole') ? $manager->employeeRole?->name : null,
                ];
            }),
            'organization_level' => $this->whenLoaded('organizationLevel', function () {
                $level = $this->resource->organizationLevel;
                if ($level === null) {
                    return null;
                }

                return [
                    'id' => $level->id,
                    'name' => $level->name,
                ];
            }),
            'skills' => $this->whenLoaded('skills', fn () => SkillResource::collection($this->resource->skills)),
            'certificates' => $this->whenLoaded('certificates', function () {
                return $this->resource->certificates->map(function ($certificate) {
                    $expires = $certificate->pivot?->expires_at;

                    return [
                        ...(new CertificateResource($certificate))->toArray(request()),
                        'expires_at' => $expires instanceof \DateTimeInterface ? $expires->format('Y-m-d') : $expires,
                    ];
                })->values();
            }),
            ...$this->searchMetadata($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchMetadata(Request $request): array
    {
        $term = trim((string) $request->input('search', ''));
        if ($term === '') {
            return [];
        }

        return [
            'expertise_matches' => ExpertiseExplanation::forEmployee($this->resource, $term),
            'match_reason' => ExpertiseExplanation::reason($this->resource, $term),
        ];
    }
}
