<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\EmployeeRoleFactory;
use Domain\Employees\DataTransferObjects\EmployeeRoleData;
use Domain\Employees\QueryBuilders\EmployeeRoleQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(EmployeeRoleQueryBuilder::class)]
class EmployeeRole extends Model
{
    /** @use HasFactory<EmployeeRoleFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<EmployeeRoleData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'employee_roles';

    /** {@inheritdoc} */
    protected string $dataClass = EmployeeRoleData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'parent_id',
        'sort_order',
        'organization_level_id',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function organizationLevel(): BelongsTo
    {
        return $this->belongsTo(OrganizationLevel::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_roles')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EmployeeRoleFactory
    {
        return EmployeeRoleFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (EmployeeRole $role): void {
            if ($role->sort_order === null) {
                $role->sort_order = 0;
            }
        });
    }
}
