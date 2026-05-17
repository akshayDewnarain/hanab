<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DataTransferObjects\UserData;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Support\Actions\BaseAction;

class CreateUserAction extends BaseAction
{
    public function execute(UserData $data): User
    {
        $model = new User;
        $model->fill([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
        $model->save();

        if ($data->role_id !== null) {
            $role = Role::query()->findOrFail($data->role_id);
            $model->syncRoles([$role]);
        }

        return $model->fresh(['roles']);
    }
}
