<?php

namespace Domain\Auth\Http\Requests\QueryBuilders;

use Domain\Auth\Helpers\RoleHelper;
use Domain\Auth\Models\Role;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class RoleQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(Role::class)
            ->defaultSort('-id')
            ->allowedFilters(RoleHelper::filterable())
            ->allowedIncludes(RoleHelper::relations())
            ->allowedSorts(RoleHelper::sortable())
            ->search(RoleHelper::searchable());
    }
}
