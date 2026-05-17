<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class EmployeeLocationData extends BaseData
{
    public function __construct(
        public string $name,
        public string $cluster_name,
        public bool $is_active,
        public ?int $id = null,
    ) {}
}
