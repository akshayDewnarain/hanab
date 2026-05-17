<?php

namespace Domain\Support\Http\Controllers;

use Domain\Support\Enums\AppScopePrefix;
use Domain\Support\Http\Requests\QueryBuilders\BaseQueryBuilder;

abstract class BaseAPICrudController extends BaseCrudController
{
    protected function appScopePrefix(): AppScopePrefix
    {
        return AppScopePrefix::API;
    }

    protected function queryClass(): mixed
    {
        return BaseQueryBuilder::class;
    }
}
