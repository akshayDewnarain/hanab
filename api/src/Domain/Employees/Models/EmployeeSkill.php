<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\EmployeeSkillFactory;
use Domain\Employees\DataTransferObjects\EmployeeSkillData;
use Domain\Employees\QueryBuilders\EmployeeSkillQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(EmployeeSkillQueryBuilder::class)]
class EmployeeSkill extends Model
{
    /** @use HasFactory<EmployeeSkillFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<EmployeeSkillData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'employee_skills';

    /** {@inheritdoc} */
    protected string $dataClass = EmployeeSkillData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'employee_id',
        'skill_id',
        'level',
        'notes',
        'assessed_at',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'level' => 'integer',
        'assessed_at' => 'datetime',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_skills')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EmployeeSkillFactory
    {
        return EmployeeSkillFactory::new();
    }
}
