<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'employee_number',
            'employment_type',
            'skills' => [
                'name',
                'code',
            ],
            'certificates' => [
                'name',
                'code',
            ],
            'employeeRole' => [
                'name',
            ],
            'employeeLocation' => [
                'name',
                'cluster_name',
            ],
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'first_name',
            'last_name',
            'email',
            'employee_number',
            'employment_type',
            'start_date',
            'is_active',
            'employee_role_id',
            'employee_location_id',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::partial('first_name'),
            AllowedFilter::partial('last_name'),
            AllowedFilter::partial('email'),
            AllowedFilter::partial('employee_number'),
            AllowedFilter::exact('employment_type'),
            AllowedFilter::exact('is_active'),
            AllowedFilter::exact('employee_role_id'),
            AllowedFilter::exact('employee_location_id'),
            AllowedFilter::scope('employee_role_name'),
            AllowedFilter::scope('employee_location_name'),
            AllowedFilter::scope('skill_ids'),
            AllowedFilter::scope('certificate_ids'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employeeRole',
            'employeeLocation',
            'skills',
            'certificates',
            'employeeSkills',
            'employeeCertificates',
        ];
    }
}
