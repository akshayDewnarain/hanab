<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Actions\BaseAction;

class DeleteEmployeeRoleAction extends BaseAction
{
    public function execute(EmployeeRole $model): bool
    {
        return $model->delete();
    }
}
