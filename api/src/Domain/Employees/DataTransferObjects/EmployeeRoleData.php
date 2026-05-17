<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class EmployeeRoleData extends BaseData
{
    public function __construct(
        public string $name,
        public bool $is_active,
        public ?int $id = null,
        public ?string $description = null,
    ) {}
}
