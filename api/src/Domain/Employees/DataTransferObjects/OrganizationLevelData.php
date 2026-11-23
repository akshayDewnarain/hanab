<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class OrganizationLevelData extends BaseData
{
    public function __construct(
        public string $name,
        public string $color,
        public bool $is_active,
        public ?int $id = null,
        public ?int $sort_order = 0,
    ) {}
}
