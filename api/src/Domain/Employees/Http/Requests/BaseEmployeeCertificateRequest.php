<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseEmployeeCertificateRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $employeeCertificate = $this->route('employee_certificate');
        $employeeCertificate = $employeeCertificate instanceof EmployeeCertificate ? $employeeCertificate : null;

        $certificateNumber = $this->input('certificate_number');

        $tripleUnique = Rule::unique('employee_certificates', 'employee_id')
            ->where('certificate_id', $this->input('certificate_id'));

        if ($certificateNumber === null || $certificateNumber === '') {
            $tripleUnique->whereNull('certificate_number');
        } else {
            $tripleUnique->where('certificate_number', $certificateNumber);
        }

        if ($employeeCertificate !== null) {
            $tripleUnique->ignore($employeeCertificate->id);
        }

        return [
            'certificate_id' => [
                'required',
                'integer',
                Rule::exists('certificates', 'id'),
            ],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id'),
                $tripleUnique,
            ],
            'certificate_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'issued_at' => [
                'nullable',
                'date',
            ],
            'expires_at' => [
                'nullable',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function model(): mixed
    {
        return EmployeeCertificate::class;
    }
}
