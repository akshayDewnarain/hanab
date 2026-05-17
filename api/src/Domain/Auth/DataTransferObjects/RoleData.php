<?php

namespace Domain\Auth\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class RoleData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $guard_name = null,
        public ?array $permissions = null,
        public ?int $id = null,
    ) {}
}
