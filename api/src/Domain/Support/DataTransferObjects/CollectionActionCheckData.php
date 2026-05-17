<?php

namespace Domain\Support\DataTransferObjects;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\Validator;

class CollectionActionCheckData extends BaseData
{
    public function __construct(
        /** @var Collection|LazyCollection<Model> $eligible */
        public ?Collection $eligible = null,
        /** @var Collection|LazyCollection<Model> $ineligible */
        public ?Collection $ineligible = null,
        public ?Validator $validator = null,
    ) {}
}
