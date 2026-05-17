<?php

namespace Domain\Support\DataTransferObjects;

use Ramsey\Collection\Collection;

class CollectionActionData extends BaseData
{
    public function __construct(
        public array $ids,
        public ?Collection $data = null,
    ) {}

    public function get(string $property)
    {
        return $this->data[$property] ?? null;
    }
}
