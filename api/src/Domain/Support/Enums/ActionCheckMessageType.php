<?php

namespace Domain\Support\Enums;

enum ActionCheckMessageType: string
{
    case Error = 'error';
    case Info = 'info';
    case Warning = 'warning';
}
