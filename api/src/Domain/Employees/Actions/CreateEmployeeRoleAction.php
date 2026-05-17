<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeRoleData;
use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Actions\BaseAction;

class CreateEmployeeRoleAction extends BaseAction
{
    public function execute(EmployeeRoleData $data): EmployeeRole
    {
        $model = new EmployeeRole;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
