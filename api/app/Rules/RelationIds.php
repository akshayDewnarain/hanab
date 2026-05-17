<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class RelationIds implements ValidationRule
{
    public function __construct(
        protected string $table,
        protected string $column = 'id',
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail(__('validation.array', ['attribute' => $attribute]));

            return;
        }

        $ids = [];

        foreach ($value as $id) {
            if (is_int($id)) {
                $ids[] = $id;

                continue;
            }

            if (is_string($id) && ctype_digit($id)) {
                $ids[] = (int) $id;

                continue;
            }

            $fail(__('validation.integer', ['attribute' => $attribute]));

            return;
        }

        $unique = array_values(array_unique($ids));

        if ($unique === []) {
            return;
        }

        $found = DB::table($this->table)->whereIn($this->column, $unique)->count();

        if ($found !== count($unique)) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
