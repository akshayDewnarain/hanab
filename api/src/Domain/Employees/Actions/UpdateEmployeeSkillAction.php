<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeSkillData;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Actions\BaseAction;

class UpdateEmployeeSkillAction extends BaseAction
{
    public function execute(EmployeeSkill $model, EmployeeSkillData $data): EmployeeSkill
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
