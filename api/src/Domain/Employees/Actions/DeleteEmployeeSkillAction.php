<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Actions\BaseAction;

class DeleteEmployeeSkillAction extends BaseAction
{
    public function execute(EmployeeSkill $model): bool
    {
        return $model->delete();
    }
}
