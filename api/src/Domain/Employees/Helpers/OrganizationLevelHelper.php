<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class OrganizationLevelHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'sort_order',
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
        return [];
    }
}
