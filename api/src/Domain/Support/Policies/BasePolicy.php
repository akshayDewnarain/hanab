<?php

namespace Domain\Support\Policies;

use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of Model */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /** @return class-string<TModel> */
    abstract protected function model(): string;

    public function viewAny(User $auth): Response|bool
    {
        return $auth->can(PermissionService::viewPermission($this->model()));
    }

    /** @param TModel $model */
    public function view(User $auth, $model): Response|bool
    {
        return $auth->can(PermissionService::viewPermission($this->model()));
    }

    public function create(User $auth): Response|bool
    {
        return $auth->can(PermissionService::createPermission($this->model()));
    }

    /** @param TModel $model */
    public function update(User $auth, $model): Response|bool
    {
        return $auth->can(PermissionService::updatePermission($this->model()));
    }

    /** @param TModel $model */
    public function delete(User $auth, $model): Response|bool
    {
        return $auth->can(PermissionService::deletePermission($this->model()));
    }
}
