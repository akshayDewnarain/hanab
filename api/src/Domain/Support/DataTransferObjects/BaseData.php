<?php

namespace Domain\Support\DataTransferObjects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

abstract class BaseData extends Data
{
    /**
     * @param  Model|Authenticatable  $model
     *
     * @throws ValidationException
     */
    public static function fromUpdateRequest($model, FormRequest|Validator $request): static
    {
        return static::from([
            ...$model->toArray(),
            ...$request->validated(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpdateData(Model $model): array
    {
        $data = $this->all();

        return array_filter($data, fn (string $key): bool => in_array($key, $model->getFillable()), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Distinguish "not provided" (null) from "provided empty" ([] to clear).
     *
     * Example: hasRelationalIds('dishIds') checks if $this->dishIds !== null.
     */
    public function hasRelationalIds(string $property): bool
    {
        if (! property_exists($this, $property)) {
            throw new InvalidArgumentException("Property {$property} does not exist on ".static::class);
        }

        return $this->{$property} !== null;
    }
}
