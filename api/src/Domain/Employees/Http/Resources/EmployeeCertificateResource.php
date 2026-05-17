<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read EmployeeCertificate $resource */
abstract class EmployeeCertificateResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'employee_id',
            'certificate_id',
            'certificate_number',
            'issued_at',
            'expires_at',
            'notes',
        ]);
    }
}
