<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DataTransferObjects\UserData;
use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Exceptions\LastSuperAdminException;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\SuperAdminGuard;
use Domain\Support\Actions\BaseAction;

class UpdateUserAction extends BaseAction
{
    public function execute(User $model, UserData $data): User
    {
        $payload = $data->getUpdateData($model);

        unset($payload['password'], $payload['id'], $payload['role_id'], $payload['email']);

        if ($data->status === UserStatus::Disabled->value && SuperAdminGuard::isLastActive($model)) {
            throw new LastSuperAdminException();
        }

        if ($data->role_id !== null) {
            $role = Role::query()->findOrFail($data->role_id);
            $removesSuperAdmin = $model->hasRole(BootstrapRoleNames::SUPER_ADMIN)
                && $role->name !== BootstrapRoleNames::SUPER_ADMIN;
            if ($removesSuperAdmin && SuperAdminGuard::isLastActive($model)) {
                throw new LastSuperAdminException();
            }
        }

        if ($data->status === UserStatus::Disabled->value) {
            $model->tokens()->delete();
        }

        $model->update([
            ...$payload,
            'email' => $data->email,
        ]);

        if ($data->role_id !== null) {
            $role = Role::query()->findOrFail($data->role_id);
            $model->syncRoles([$role]);
        }

        return $model->fresh(['roles']);
    }
}
