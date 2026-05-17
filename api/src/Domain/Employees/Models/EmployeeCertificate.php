<?php

namespace Domain\Employees\Models;

use Domain\Employees\Database\Factories\EmployeeCertificateFactory;
use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\QueryBuilders\EmployeeCertificateQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;

#[UseEloquentBuilder(EmployeeCertificateQueryBuilder::class)]
class EmployeeCertificate extends Model
{
    /** @use HasFactory<EmployeeCertificateFactory> */
    use HasFactory;

    use LogsActivity;

    /** @use WithData<EmployeeCertificateData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'employee_certificates';

    /** {@inheritdoc} */
    protected string $dataClass = EmployeeCertificateData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'employee_id',
        'certificate_id',
        'certificate_number',
        'issued_at',
        'expires_at',
        'notes',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_certificates')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): EmployeeCertificateFactory
    {
        return EmployeeCertificateFactory::new();
    }
}
