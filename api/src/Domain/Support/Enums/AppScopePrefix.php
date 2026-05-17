<?php

namespace Domain\Support\Enums;

enum AppScopePrefix: string
{
    case API = 'api';
    case PUBLIC = 'public';

    public function format(): string
    {
        return mb_strtoupper($this->value);
    }
}
