<?php

namespace Domain\Employees\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeImportLog extends Model
{
    /** {@inheritdoc} */
    protected $table = 'employee_import_logs';

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'file_name',
        'outcome',
        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',
        'error_count',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'user_id' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'unchanged_count' => 'integer',
        'skipped_count' => 'integer',
        'error_count' => 'integer',
    ];
}
