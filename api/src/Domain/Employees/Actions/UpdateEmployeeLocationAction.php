<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeLocationData;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Actions\BaseAction;

class UpdateEmployeeLocationAction extends BaseAction
{
    public function execute(EmployeeLocation $model, EmployeeLocationData $data): EmployeeLocation
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
