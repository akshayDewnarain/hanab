<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class ExpertiseSearchResultData extends BaseData
{
    /**
     * @param  list<array{kind: string, name: string, code: ?string, matched_on: string, synonym: ?string}>  $matches
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $role,
        public ?string $location,
        public ?string $email,
        public ?string $phone,
        public array $matches,
    ) {}
}
