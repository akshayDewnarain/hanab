<?php

namespace Domain\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseRequest extends FormRequest
{
    /**
     * Abstract method for defining general validation rules.
     */
    abstract public function rules(): array;

    protected function convertToApplicationTimezone(string $value): string
    {
        try {
            return Carbon::parse($value)->timezone(config('app.timezone'))->toIso8601String();
        } catch (Throwable $e) {
            Log::error($e);

            return $value;
        }
    }
}
