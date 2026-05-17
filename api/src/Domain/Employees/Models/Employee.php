<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\EmployeeFactory;
use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\QueryBuilders\EmployeeQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\LaravelData\WithData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UseEloquentBuilder(EmployeeQueryBuilder::class)]
class Employee extends Model implements HasMedia
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use LogsActivity;

    /** @use WithData<EmployeeData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'employees';

    /** {@inheritdoc} */
    protected string $dataClass = EmployeeData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'employee_role_id',
        'employee_location_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_number',
        'employment_type',
        'start_date',
        'is_active',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'employee_role_id' => 'integer',
        'employee_location_id' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->useDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        /** Square thumbnail for cards */
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->format('webp')
            ->nonQueued();

        /** Larger web-optimized image */
        $this->addMediaConversion('web')
            ->fit(Fit::Contain, 1200, 1200)
            ->format('webp');
    }

    public function employeeRole(): BelongsTo
    {
        return $this->belongsTo(EmployeeRole::class);
    }

    public function employeeLocation(): BelongsTo
    {
        return $this->belongsTo(EmployeeLocation::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'employee_skills')
            ->withPivot(['level', 'notes', 'assessed_at'])
            ->withTimestamps();
    }

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class, 'employee_certificates')
            ->withPivot(['certificate_number', 'issued_at', 'expires_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Sync skill links from id list. New pivots get default level 1; existing pivot data is preserved.
     *
     * @param  list<int|string>  $skillIds
     */
    public function syncSkillIds(array $skillIds): void
    {
        $skillIds = array_values(array_unique(array_map(intval(...), $skillIds)));

        if ($skillIds === []) {
            $this->skills()->sync([]);

            return;
        }

        $existing = $this->skills()
            ->whereIn('skills.id', $skillIds)
            ->get()
            ->keyBy('id');

        $sync = [];
        foreach ($skillIds as $skillId) {
            $pivot = $existing->get($skillId)?->pivot;
            $sync[$skillId] = [
                'level' => $pivot?->level ?? 1,
                'notes' => $pivot?->notes,
                'assessed_at' => $pivot?->assessed_at,
            ];
        }

        $this->skills()->sync($sync);
    }

    /**
     * @param  array<int|string>|string|int|null  $value
     */
    public function scopeEmployeeRoleName(Builder $query, mixed $value): Builder
    {
        return static::filterByRelatedName($query, 'employeeRole', $value);
    }

    /**
     * @param  array<int|string>|string|int|null  $value
     */
    public function scopeEmployeeLocationName(Builder $query, mixed $value): Builder
    {
        return static::filterByRelatedName($query, 'employeeLocation', $value);
    }

    /**
     * @param  array<int|string>|string|int|null  $value
     */
    public function scopeSkillIds(Builder $query, mixed $value): Builder
    {
        $ids = collect(Arr::wrap($value))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return $query;
        }

        return $query->whereHas(
            'skills',
            fn (Builder $skillQuery) => $skillQuery->whereIn('skills.id', $ids),
        );
    }

    /**
     * @param  array<int|string>|string|int|null  $value
     */
    public function scopeCertificateIds(Builder $query, mixed $value): Builder
    {
        $ids = collect(Arr::wrap($value))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return $query;
        }

        return $query->whereHas(
            'certificates',
            fn (Builder $certificateQuery) => $certificateQuery->whereIn('certificates.id', $ids),
        );
    }

    /**
     * Partial match on a belongs-to relation's `name` column.
     *
     * @param  array<int|string>|string|int|null  $value
     */
    protected static function filterByRelatedName(Builder $query, string $relation, mixed $value): Builder
    {
        $term = trim((string) $value);
        if ($term === '') {
            return $query;
        }

        $pattern = '%'.Str::replace(' ', '%', $term).'%';

        return $query->whereHas(
            $relation,
            fn (Builder $relationQuery) => $relationQuery->where('name', 'LIKE', $pattern),
        );
    }

    public function employeeSkills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function employeeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employees')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
