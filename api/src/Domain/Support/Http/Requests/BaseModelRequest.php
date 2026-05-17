<?php

namespace Domain\Support\Http\Requests;

abstract class BaseModelRequest extends BaseRequest
{
    abstract protected function model(): mixed;

    public function attributes()
    {
        return parent::attributes();
    }
}
