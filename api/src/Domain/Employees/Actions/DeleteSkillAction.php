<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Models\Skill;
use Domain\Support\Actions\BaseAction;

class DeleteSkillAction extends BaseAction
{
    public function execute(Skill $model): bool
    {
        return $model->delete();
    }
}
