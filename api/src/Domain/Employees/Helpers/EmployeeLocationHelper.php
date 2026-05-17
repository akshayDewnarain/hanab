<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeLocationHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
            'cluster_name',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'cluster_name',
            'is_active',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('cluster_name'),
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
