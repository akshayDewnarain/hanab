<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeSkillHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'notes',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'employee_id',
            'skill_id',
            'level',
            'assessed_at',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::exact('employee_id'),
            AllowedFilter::exact('skill_id'),
            AllowedFilter::exact('level'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employee',
            'skill',
        ];
    }
}
