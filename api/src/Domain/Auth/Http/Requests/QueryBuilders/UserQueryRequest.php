<?php

namespace Domain\Auth\Http\Requests\QueryBuilders;

use Domain\Auth\Helpers\UserHelper;
use Domain\Auth\Models\User;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class UserQueryRequest extends BaseModelQueryRequest
{
    public function queryBuilder(): BaseQueryBuilder
    {
        return $this->baseQueryBuilder(User::class)
            ->defaultSort('-id')
            ->allowedFilters(UserHelper::filterable())
            ->allowedIncludes(UserHelper::relations())
            ->allowedSorts(UserHelper::sortable())
            ->search(UserHelper::searchable());
    }
}
