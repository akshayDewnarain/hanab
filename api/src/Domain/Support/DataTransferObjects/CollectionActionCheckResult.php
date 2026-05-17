<?php

namespace Domain\Support\DataTransferObjects;

class CollectionActionCheckResult extends ActionResult
{
    public function __construct(CollectionActionCheckData $data)
    {
        parent::__construct($data);
    }
}
