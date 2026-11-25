<?php

namespace Application\API\Auth\Resources;

use Domain\Auth\Http\Resources\RoleResource as DomainRoleResource;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\ReservedContentPermissions;
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
                function () use ($request) {
                    $permissions = $this->resource->permissions;
                    $actor = $request->user();
                    if (! ($actor instanceof User && $actor->hasRole(BootstrapRoleNames::SUPER_ADMIN))) {
                        $permissions = $permissions->reject(
                            static fn ($permission): bool => ReservedContentPermissions::isReserved($permission->name),
                        );
                    }

                    return $permissions->map(static fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values()->all();
                },
            ),
        ];
    }
}
