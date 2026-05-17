<?php

namespace Domain\Auth\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class RoleHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
            'guard_name',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'guard_name',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('name'),
            AllowedFilter::exact('guard_name'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'permissions',
        ];
    }
}
