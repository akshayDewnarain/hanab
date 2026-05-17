<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Actions\BaseAction;

class DeleteEmployeeLocationAction extends BaseAction
{
    public function execute(EmployeeLocation $model): bool
    {
        return $model->delete();
    }
}
