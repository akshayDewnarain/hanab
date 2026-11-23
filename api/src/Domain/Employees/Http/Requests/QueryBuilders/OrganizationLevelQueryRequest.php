<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Employees\Helpers\OrganizationLevelHelper;
use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class OrganizationLevelQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(OrganizationLevel::class)
            ->defaultSort('sort_order')
            ->allowedFilters(OrganizationLevelHelper::filterable())
            ->allowedIncludes(OrganizationLevelHelper::relations())
            ->allowedSorts(OrganizationLevelHelper::sortable())
            ->search(OrganizationLevelHelper::searchable());
    }
}
