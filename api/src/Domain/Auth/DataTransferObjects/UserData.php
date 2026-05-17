<?php

namespace Domain\Auth\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class UserData extends BaseData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?int $role_id = null,
        public ?string $password = null,
        public ?int $id = null,
    ) {}
}
