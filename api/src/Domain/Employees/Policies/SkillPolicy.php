<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\Skill;
use Domain\Support\Policies\BasePolicy;

class SkillPolicy extends BasePolicy
{
    protected function model(): string
    {
        return Skill::class;
    }
}
