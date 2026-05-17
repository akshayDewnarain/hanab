<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DataTransferObjects\RoleData;
use Domain\Auth\Models\Role;
use Domain\Support\Actions\BaseAction;

class CreateRoleAction extends BaseAction
{
    public function execute(RoleData $data): Role
    {
        $guard = $data->guard_name ?? config('auth.defaults.guard');

        $model = Role::query()->create([
            'name' => $data->name,
            'guard_name' => $guard,
        ]);

        if ($data->permissions !== null) {
            $model->syncPermissions($data->permissions);
        }

        return $model->fresh(['permissions']);
    }
}
