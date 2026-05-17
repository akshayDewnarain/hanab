<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeSkillHelper;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class EmployeeSkillQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(EmployeeSkill::class)
            ->defaultSort('-id')
            ->allowedFilters(EmployeeSkillHelper::filterable())
            ->allowedIncludes(EmployeeSkillHelper::relations())
            ->allowedSorts(EmployeeSkillHelper::sortable())
            ->search(EmployeeSkillHelper::searchable());
    }
}
