<?php

namespace Domain\Support\DataTransferObjects;

class CollectionActionInputData extends BaseData
{
    public function __construct(
        public CollectionActionCheckData $checkData,
        public ?array $data = null,
        public ?User $user = null,
    ) {}
}
