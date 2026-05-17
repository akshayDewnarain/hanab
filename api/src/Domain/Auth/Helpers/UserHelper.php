<?php

namespace Domain\Auth\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class UserHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
            'email',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'email',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('email'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'roles',
        ];
    }
}
