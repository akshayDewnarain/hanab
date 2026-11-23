<?php

namespace Domain\Support\Transfer;

use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Checks mapped rows against the model's existing create rules.
 * It does not update, match an existing record, or delete.
 */
final class TransferInspector
{
    public const int MAX_ROWS = 2000;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     valid: int,
     *     invalid: int,
     *     skipped: int,
     *     errors: list<array{row: int, messages: list<string>}>,
     *     payloads: list<array<string, mixed>>
     * }
     */
    public function inspect(TransferModel $model, array $rows): array
    {
        $fields = $model->fields();
        $known = [];
        foreach ($fields as $field) {
            $known[$field['id']] = $field['type'];
        }
        $dataKeys = array_flip($model->dataKeys());

        $valid = 0;
        $invalid = 0;
        $skipped = 0;
        $errors = [];
        $payloads = [];

        foreach (array_values($rows) as $index => $row) {
            $rowNumber = $index + 1;
            if (! is_array($row)) {
                $invalid++;
                $errors[] = ['row' => $rowNumber, 'messages' => ['This row is not a record.']];

                continue;
            }

            $unknown = array_values(array_diff(array_keys($row), array_keys($known)));
            if ($unknown !== []) {
                $invalid++;
                $errors[] = ['row' => $rowNumber, 'messages' => ['Unknown field: '.implode(', ', $unknown)]];

                continue;
            }

            $cast = $this->cast($row, $known);
            if ($this->isEmpty($cast)) {
                $skipped++;

                continue;
            }

            $validator = Validator::make($cast, $model->rulesFor($cast));
            if ($validator->fails()) {
                $invalid++;
                $errors[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];

                continue;
            }

            $payload = [];
            foreach ($cast as $key => $value) {
                if (isset($dataKeys[$key])) {
                    $payload[$key] = $value;
                }
            }

            try {
                $model->dataClass::from($payload);
            } catch (Throwable) {
                $invalid++;
                $errors[] = ['row' => $rowNumber, 'messages' => ['This row does not match the model.']];

                continue;
            }

            $valid++;
            $payloads[] = $payload;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'skipped' => $skipped,
            'errors' => $errors,
            'payloads' => $payloads,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $types
     * @return array<string, mixed>
     */
    private function cast(array $row, array $types): array
    {
        $cast = [];
        foreach ($row as $key => $value) {
            $cast[$key] = $this->castValue($types[$key] ?? 'string', $value);
        }

        return $cast;
    }

    private function castValue(string $type, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'boolean' => $this->castBoolean($value),
            'integer' => is_numeric($value) ? (int) $value : $value,
            'array' => $this->castList($value),
            'date' => $this->castDate($value),
            default => is_scalar($value) ? (string) $value : $value,
        };
    }

    private function castBoolean(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $text = mb_strtolower(trim((string) $value));

        return match ($text) {
            '1', 'true', 'yes', 'y', 'ja', 'waar' => true,
            '0', 'false', 'no', 'n', 'nee', 'onwaar' => false,
            default => $value,
        };
    }

    /**
     * @return list<string>|mixed
     */
    private function castList(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $value), fn ($item) => $item !== ''));
        }
        if (! is_string($value)) {
            return $value;
        }

        $parts = preg_split('/[\n,]+/', $value) ?: [];

        return array_values(array_filter(array_map(trim(...), $parts), fn (string $item) => $item !== ''));
    }

    private function castDate(mixed $value): mixed
    {
        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial > 20000 && $serial < 80000) {
                return gmdate('Y-m-d', (int) round(($serial - 25569) * 86400));
            }
        }

        return is_scalar($value) ? (string) $value : $value;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            return false;
        }

        return true;
    }
}
