<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeLocationData;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Actions\BaseAction;

class CreateEmployeeLocationAction extends BaseAction
{
    public function execute(EmployeeLocationData $data): EmployeeLocation
    {
        $model = new EmployeeLocation;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
