<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeRoleData;
use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Actions\BaseAction;

class UpdateEmployeeRoleAction extends BaseAction
{
    public function execute(EmployeeRole $model, EmployeeRoleData $data): EmployeeRole
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
