<?php

namespace Domain\Support\DataTransferObjects;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class ActionCollectionData extends BaseData
{
    public function __construct(
        public Collection|LazyCollection $models,
        public ?array $data = null,
    ) {}
}
