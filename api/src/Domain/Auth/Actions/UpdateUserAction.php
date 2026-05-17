<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DataTransferObjects\UserData;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Support\Actions\BaseAction;

class UpdateUserAction extends BaseAction
{
    public function execute(User $model, UserData $data): User
    {
        $payload = $data->getUpdateData($model);

        if ($data->password === null || $data->password === '') {
            unset($payload['password']);
        }

        $model->update($payload);

        if ($data->role_id !== null) {
            $role = Role::query()->findOrFail($data->role_id);
            $model->syncRoles([$role]);
        }

        return $model->fresh(['roles']);
    }
}
