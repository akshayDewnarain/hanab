<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\SkillHelper;
use Domain\Employees\Models\Skill;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class SkillQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(Skill::class)
            ->defaultSort('-id')
            ->allowedFilters(SkillHelper::filterable())
            ->allowedIncludes(SkillHelper::relations())
            ->allowedSorts(SkillHelper::sortable())
            ->search(SkillHelper::searchable());
    }
}
