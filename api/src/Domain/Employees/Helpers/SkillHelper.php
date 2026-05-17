<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class SkillHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'name',
            'code',
            'category',
            'description',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'name',
            'code',
            'category',
            'is_active',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('name'),
            AllowedFilter::exact('category'),
            AllowedFilter::exact('is_active'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employees',
            'employeeSkills',
        ];
    }
}
