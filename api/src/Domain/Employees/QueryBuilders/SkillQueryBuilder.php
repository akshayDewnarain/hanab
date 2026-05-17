<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\SkillHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class SkillQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return SkillHelper::class;
    }
}
