<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\OrganizationLevelFactory;
use Domain\Employees\DataTransferObjects\OrganizationLevelData;
use Domain\Employees\QueryBuilders\OrganizationLevelQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(OrganizationLevelQueryBuilder::class)]
class OrganizationLevel extends Model
{
    /** @use HasFactory<OrganizationLevelFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<OrganizationLevelData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'organization_levels';

    /** {@inheritdoc} */
    protected string $dataClass = OrganizationLevelData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'sort_order',
        'color',
        'is_active',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function employeeRoles(): HasMany
    {
        return $this->hasMany(EmployeeRole::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('organization_levels')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): OrganizationLevelFactory
    {
        return OrganizationLevelFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (OrganizationLevel $level): void {
            if ($level->sort_order === null) {
                $level->sort_order = 0;
            }
        });
    }
}
