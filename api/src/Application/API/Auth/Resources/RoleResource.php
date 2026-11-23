<?php

namespace Application\API\Auth\Resources;

use Domain\Auth\Http\Resources\RoleResource as DomainRoleResource;
use Domain\Auth\Models\Role;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/** @property-read Role $resource */
class RoleResource extends DomainRoleResource
{
    public function toArray($request): array|Arrayable|JsonSerializable
    {
        return [
            ...parent::toArray($request),
            'users_count' => $this->resource->users_count ?? null,
            'permissions' => $this->when(
                $this->resource->relationLoaded('permissions'),
                fn () => $this->resource->permissions->map(static fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])->values()->all(),
            ),
        ];
    }
}
