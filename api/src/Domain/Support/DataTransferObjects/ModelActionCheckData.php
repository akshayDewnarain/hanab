<?php

namespace Domain\Support\DataTransferObjects;

use Domain\Support\Enums\ActionCheckMessageType;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class ModelActionCheckData extends BaseData
{
    public function __construct(
        public ?Model $model = null,
        public ?bool $eligible = null,
        public ?string $message = null,
        #[WithCast(EnumCast::class)]
        public ?ActionCheckMessageType $message_type = null,
    ) {}
}
