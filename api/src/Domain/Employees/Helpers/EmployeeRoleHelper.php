<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeRoleHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
            'description',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'is_active',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('name'),
            AllowedFilter::exact('is_active'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employees',
        ];
    }
}
