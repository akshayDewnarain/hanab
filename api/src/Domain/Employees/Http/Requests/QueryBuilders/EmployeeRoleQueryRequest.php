<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\EmployeeRoleHelper;
use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class EmployeeRoleQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(EmployeeRole::class)
            ->defaultSort('-id')
            ->allowedFilters(EmployeeRoleHelper::filterable())
            ->allowedIncludes(EmployeeRoleHelper::relations())
            ->allowedSorts(EmployeeRoleHelper::sortable())
            ->search(EmployeeRoleHelper::searchable());
    }
}
