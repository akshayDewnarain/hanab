<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\SkillFactory;
use Domain\Employees\DataTransferObjects\SkillData;
use Domain\Employees\QueryBuilders\SkillQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(SkillQueryBuilder::class)]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<SkillData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'skills';

    /** {@inheritdoc} */
    protected string $dataClass = SkillData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'code',
        'label_background_color',
        'label_text_color',
        'label_border_color',
        'category',
        'description',
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

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_skills')
            ->withPivot(['level', 'notes', 'assessed_at'])
            ->withTimestamps();
    }

    public function employeeSkills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('skills')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): SkillFactory
    {
        return SkillFactory::new();
    }
}
