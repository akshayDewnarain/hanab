<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;
use Illuminate\Http\UploadedFile;

class EmployeeData extends BaseData
{
    public function __construct(
        public int $employee_role_id,
        public int $employee_location_id,
        public string $first_name,
        public string $last_name,
        public string $email,
        public string $employee_number,
        public string $employment_type,
        public bool $is_active,
        public ?int $id = null,
        public ?string $phone = null,
        public ?string $start_date = null,
        public ?UploadedFile $image = null,
        public ?array $skill_ids = null,
        public ?array $certificate_ids = null,
    ) {}
}
