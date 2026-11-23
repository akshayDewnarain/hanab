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
        public ?int $parent_id = null,
        public ?int $sort_order = 0,
        public ?int $organization_level_id = null,
    ) {}
}
