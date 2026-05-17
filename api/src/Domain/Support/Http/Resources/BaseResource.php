<?php

namespace Domain\Support\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /** @return array<int, string> */
    protected function baseAttributes(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
        ];
    }

    protected function withAttributes(array $attributes, array $additional = []): array
    {
        $attributes = [
            ...$this->baseAttributes(),
            ...$attributes,
        ];

        $base = collect($attributes)
            ->mapWithKeys(function (string $attribute) {
                $value = $this->resource->{$attribute};

                $value = $this->transformValue($value);

                return [$attribute => $value];
            })
            ->all();

        return [
            ...$base,
            ...$additional,
        ];
    }

    protected function transformValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            $value = $value->utc()->toIso8601String();
        }

        return $value;
    }
}
