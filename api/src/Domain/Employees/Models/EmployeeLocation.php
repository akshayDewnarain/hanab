<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\EmployeeLocationFactory;
use Domain\Employees\DataTransferObjects\EmployeeLocationData;
use Domain\Employees\QueryBuilders\EmployeeLocationQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(EmployeeLocationQueryBuilder::class)]
class EmployeeLocation extends Model
{
    /** @use HasFactory<EmployeeLocationFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<EmployeeLocationData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'employee_locations';

    /** {@inheritdoc} */
    protected string $dataClass = EmployeeLocationData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'cluster_name',
        'is_active',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_locations')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EmployeeLocationFactory
    {
        return EmployeeLocationFactory::new();
    }
}
