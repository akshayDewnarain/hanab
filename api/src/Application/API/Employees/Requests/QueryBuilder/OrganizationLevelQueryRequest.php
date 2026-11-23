<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\OrganizationLevelQueryRequest as DomainOrganizationLevelQueryRequest;

class OrganizationLevelQueryRequest extends DomainOrganizationLevelQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
