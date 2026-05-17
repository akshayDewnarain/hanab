<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\Employee;
use Domain\Support\Actions\BaseAction;

class DeleteEmployeeAction extends BaseAction
{
    public function execute(Employee $model): bool
    {
        return $model->delete();
    }
}
