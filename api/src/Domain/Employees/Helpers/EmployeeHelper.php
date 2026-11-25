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
            'initials',
            'email',
            'phone',
            'business_email',
            'business_phone',
            'employee_number',
            'employment_type',
            'skills' => [
                'name',
                'code',
                'synonyms',
            ],
            'certificates' => [
                'name',
                'code',
                'synonyms',
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
            'initials',
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
            AllowedFilter::partial('initials'),
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
            AllowedFilter::scope('missing_skill'),
            AllowedFilter::scope('missing_certificate'),
            AllowedFilter::scope('missing_location'),
            AllowedFilter::scope('missing_email'),
            AllowedFilter::scope('missing_phone'),
            AllowedFilter::scope('missing_business_email'),
            AllowedFilter::scope('missing_business_phone'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employeeRole',
            'employeeLocation',
            'manager.employeeRole',
            'organizationLevel',
            'skills',
            'certificates',
            'employeeSkills',
            'employeeCertificates',
        ];
    }
}
