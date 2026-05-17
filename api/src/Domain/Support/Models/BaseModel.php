<?php

namespace Domain\Support\Models;

use DateTimeInterface;
use Domain\Support\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use LogsActivity;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
