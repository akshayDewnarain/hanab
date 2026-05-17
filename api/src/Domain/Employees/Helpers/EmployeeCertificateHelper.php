<?php

namespace Domain\Employees\Helpers;

use Domain\Support\Helpers\BaseHelper;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeCertificateHelper extends BaseHelper
{
    public static function searchable(): array
    {
        return [
            'certificate_number',
            'notes',
        ];
    }

    public static function sortable(): array
    {
        return array_merge(parent::sortable(), [
            'employee_id',
            'certificate_id',
            'issued_at',
            'expires_at',
        ]);
    }

    public static function filterable(): array
    {
        return array_merge(parent::filterable(), [
            AllowedFilter::exact('employee_id'),
            AllowedFilter::exact('certificate_id'),
            AllowedFilter::partial('certificate_number'),
        ]);
    }

    public static function relations(): array
    {
        return [
            'employee',
            'certificate',
        ];
    }
}
