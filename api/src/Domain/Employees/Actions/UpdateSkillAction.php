<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\SkillData;
use Domain\Employees\Models\Skill;
use Domain\Support\Actions\BaseAction;

class UpdateSkillAction extends BaseAction
{
    public function execute(Skill $model, SkillData $data): Skill
    {
        $model->update($data->getUpdateData($model));

        return $model;
    }
}
