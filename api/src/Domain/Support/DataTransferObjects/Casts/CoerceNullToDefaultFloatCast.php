<?php

namespace Domain\Support\DataTransferObjects\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Treats null / empty string as a default float (for legacy nullable DB columns).
 */
class CoerceNullToDefaultFloatCast implements Cast
{
    public function __construct(
        private readonly float $default = 0.0,
    ) {}

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): float
    {
        if ($value === null || $value === '') {
            return $this->default;
        }

        return (float) $value;
    }
}
