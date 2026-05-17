<?php

namespace Application\API\Auth\Resources;

use Domain\Auth\Http\Resources\UserResource as DomainUserResource;
use Domain\Auth\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/** @property-read User $resource */
class UserResource extends DomainUserResource
{
    public function toArray($request): array|Arrayable|JsonSerializable
    {
        $firstRole = $this->resource->relationLoaded('roles')
            ? $this->resource->roles->first()
            : $this->resource->roles()->first();

        return [
            ...parent::toArray($request),
            'role_id' => $firstRole?->id,
            'role_name' => $firstRole?->name,
            'permissions' => $this->resource->getAllPermissions()
                ->map(static fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])
                ->values()
                ->all(),
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->resource->roles->map(static fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()->all(),
            ),
            'profile_picture_url' => $this->resource->getFirstMediaUrl('profile_pictures'),
        ];
    }
}
