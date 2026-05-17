<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\CertificateFactory;
use Domain\Employees\DataTransferObjects\CertificateData;
use Domain\Employees\QueryBuilders\CertificateQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(CertificateQueryBuilder::class)]
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<CertificateData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'certificates';

    /** {@inheritdoc} */
    protected string $dataClass = CertificateData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'code',
        'label_background_color',
        'label_text_color',
        'label_border_color',
        'category',
        'description',
        'requires_expiry_date',
        'is_active',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'requires_expiry_date' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_certificates')
            ->withPivot(['certificate_number', 'issued_at', 'expires_at', 'notes'])
            ->withTimestamps();
    }

    public function employeeCertificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('certificates')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): CertificateFactory
    {
        return CertificateFactory::new();
    }
}
