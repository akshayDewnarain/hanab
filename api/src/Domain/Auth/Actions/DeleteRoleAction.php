<?php

namespace Domain\Auth\Actions;

use Domain\Auth\Models\Role;
use Domain\Support\Actions\BaseAction;

class DeleteRoleAction extends BaseAction
{
    public function execute(Role $model): bool
    {
        return (bool) $model->delete();
    }
}
