<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class EmployeeSkillData extends BaseData
{
    public function __construct(
        public int $employee_id,
        public int $skill_id,
        public int $level,
        public ?int $id = null,
        public ?string $notes = null,
        public ?string $assessed_at = null,
    ) {}
}
