<?php

namespace Domain\Auth\Actions;

use Domain\Auth\DataTransferObjects\RoleData;
use Domain\Auth\Models\Role;
use Domain\Support\Actions\BaseAction;

class UpdateRoleAction extends BaseAction
{
    public function execute(Role $model, RoleData $data): Role
    {
        $model->update($data->getUpdateData($model));

        if ($data->permissions !== null) {
            $model->syncPermissions($data->permissions);
        }

        return $model->fresh(['permissions']);
    }
}
