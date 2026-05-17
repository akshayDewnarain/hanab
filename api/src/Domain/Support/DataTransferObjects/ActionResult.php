<?php

namespace Domain\Support\DataTransferObjects;

class ActionResult extends BaseData
{
    public function __construct(
        public $data = null,
    ) {}
}
