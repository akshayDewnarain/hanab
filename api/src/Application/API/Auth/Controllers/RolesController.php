<?php

namespace Application\API\Auth\Controllers;

use Application\API\Auth\Requests\SaveRoleRequest;
use Application\API\Auth\Resources\RoleResource;
use Domain\Auth\Actions\CreateRoleAction;
use Domain\Auth\Actions\DeleteRoleAction;
use Domain\Auth\Actions\UpdateRoleAction;
use Domain\Auth\DataTransferObjects\RoleData;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Support\Helpers\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class RolesController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions')
            ->withCount($this->visibleUserCount())
            ->when(! $this->actorHasReservedRole(), fn ($query) => $query->where('name', '!=', BootstrapRoleNames::SUPER_ADMIN))
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles)->response();
    }

    public function store(SaveRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $action->execute(RoleData::from([
            'name' => $request->validated('name'),
            'permissions' => $request->validated('permissions') ?? [],
        ]));

        return (new RoleResource($role->load('permissions')->loadCount($this->visibleUserCount())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Role $role): JsonResponse
    {
        if ($this->roleIsHidden($role)) {
            abort(404);
        }

        $this->authorize('view', $role);

        return (new RoleResource($role->load('permissions')->loadCount($this->visibleUserCount())))->response();
    }

    public function update(SaveRoleRequest $request, Role $role, UpdateRoleAction $action): JsonResponse
    {
        if ($this->roleIsHidden($role)) {
            abort(404);
        }

        $this->authorize('update', $role);

        if ($role->name === BootstrapRoleNames::SUPER_ADMIN) {
            return ApiResponse::error('The super-admin role cannot be changed.');
        }

        $updated = $action->execute($role, RoleData::from([
            'name' => $request->validated('name'),
            'guard_name' => $role->guard_name,
            'permissions' => $request->validated('permissions') ?? [],
            'id' => $role->id,
        ]));

        return (new RoleResource($updated->load('permissions')->loadCount($this->visibleUserCount())))->response();
    }

    public function destroy(Role $role, DeleteRoleAction $action): JsonResponse
    {
        if ($this->roleIsHidden($role)) {
            abort(404);
        }

        $this->authorize('delete', $role);

        if ($role->name === BootstrapRoleNames::SUPER_ADMIN) {
            return ApiResponse::error('The super-admin role cannot be deleted.');
        }

        $action->execute($role);

        return ApiResponse::deleted('Role deleted.');
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json([
            'data' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array<string, \Closure>
     */
    private function visibleUserCount(): array
    {
        return [
            'users as users_count' => fn ($query) => $query->where('users.is_internal_account', false),
        ];
    }

    private function roleIsHidden(Role $role): bool
    {
        return $role->name === BootstrapRoleNames::SUPER_ADMIN && ! $this->actorHasReservedRole();
    }

    private function actorHasReservedRole(): bool
    {
        $actor = request()->user();

        return $actor instanceof User && $actor->hasRole(BootstrapRoleNames::SUPER_ADMIN);
    }
}
