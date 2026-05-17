<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\SkillData;
use Domain\Employees\Models\Skill;
use Domain\Support\Actions\BaseAction;

class CreateSkillAction extends BaseAction
{
    public function execute(SkillData $data): Skill
    {
        $model = new Skill;
        $model->fill($data->all());
        $model->save();

        return $model;
    }
}
