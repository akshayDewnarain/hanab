<?php

namespace Application\API\Auth\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Auth\Http\Requests\QueryBuilders\UserQueryRequest as DomainUserQueryRequest;

class UserQueryRequest extends DomainUserQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
