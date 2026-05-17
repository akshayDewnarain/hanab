<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class EmployeeCertificateData extends BaseData
{
    public function __construct(
        public int $employee_id,
        public int $certificate_id,
        public ?int $id = null,
        public ?string $certificate_number = null,
        public ?string $issued_at = null,
        public ?string $expires_at = null,
        public ?string $notes = null,
    ) {}
}
