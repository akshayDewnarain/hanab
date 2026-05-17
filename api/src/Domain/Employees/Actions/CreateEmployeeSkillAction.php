<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeSkillData;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Actions\BaseAction;

class CreateEmployeeSkillAction extends BaseAction
{
    public function execute(EmployeeSkillData $data): EmployeeSkill
    {
        $model = new EmployeeSkill;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
