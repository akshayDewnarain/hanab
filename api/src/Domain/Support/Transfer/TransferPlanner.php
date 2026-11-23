<?php

namespace Domain\Support\Transfer;

use DateTimeInterface;
use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Classifies mapped rows for a catalog model.
 * It matches on the model's unique key, compares supplied fields, and does not write.
 */
final class TransferPlanner
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function plan(TransferModel $model, array $rows, ?string $strategy, ?string $policy, ?User $user, bool $confirmPartial = false): array
    {
        $profile = $model->profile;
        $strategy = $strategy ?: $profile->defaultStrategy;
        $policy = $policy ?: $profile->defaultPolicy;

        if (! in_array($strategy, $profile->strategies, true)) {
            return $this->rejected('strategy', 'Choose create only, create and update, or update only.');
        }
        if (! in_array($policy, $profile->policies, true) || ($policy === 'valid_rows' && ! $profile->partial)) {
            return $this->rejected('policy', 'This model cannot import valid rows while other rows have errors.');
        }

        $types = $this->types($model);
        $prepared = [];
        $errors = [];
        $skipped = 0;
        $keys = [];

        foreach (array_values($rows) as $index => $row) {
            $rowNumber = $index + 1;
            if (! is_array($row)) {
                $errors[$rowNumber][] = $this->issue($rowNumber, '', 'invalid', 'This row is not a record.', []);

                continue;
            }

            $unknown = array_values(array_diff(array_keys($row), array_keys($types)));
            if ($unknown !== []) {
                $errors[$rowNumber][] = $this->issue($rowNumber, $unknown[0], 'invalid', 'Unknown field: '.implode(', ', $unknown), $this->original($row));

                continue;
            }

            $original = $this->original($row);
            $supplied = [];
            $states = [];
            foreach ($row as $key => $value) {
                $read = $this->interpret($types[$key] ?? 'string', $value);
                $states[$key] = $read['state'];
                if ($read['state'] === 'empty') {
                    continue;
                }
                if ($read['state'] === 'clear' && ! in_array($key, $profile->clearable, true)) {
                    $errors[$rowNumber][] = $this->issue($rowNumber, $key, 'clear', 'This field cannot be cleared.', $original);

                    continue;
                }
                $supplied[$key] = $read['value'];
            }

            if ($supplied === [] && ! in_array('clear', $states, true)) {
                $skipped++;

                continue;
            }
            if (isset($errors[$rowNumber])) {
                continue;
            }

            $keyValues = $this->keyValues($profile->match, $supplied);
            $prepared[] = [
                'row' => $rowNumber,
                'supplied' => $supplied,
                'original' => $original,
                'key' => $keyValues,
            ];
            if ($keyValues !== null) {
                $keys[$this->fingerprint($keyValues)][] = $rowNumber;
            }
        }

        foreach ($keys as $rowNumbers) {
            if (count($rowNumbers) < 2) {
                continue;
            }
            foreach ($rowNumbers as $rowNumber) {
                $errors[$rowNumber][] = $this->issue($rowNumber, $profile->match[0] ?? '', 'duplicate', 'This matching key appears more than once in the file.', []);
            }
        }

        $canCreate = $user !== null && $user->can(PermissionService::createPermission($model->modelClass));
        $canUpdate = $user !== null && $user->can(PermissionService::updatePermission($model->modelClass));
        $operations = [];
        $publicRows = [];
        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach ($prepared as $item) {
            if (isset($errors[$item['row']])) {
                continue;
            }

            $matches = $item['key'] === null ? null : $this->findMatches($model, $item['key']);
            if ($matches !== null && $matches->count() > 1) {
                $errors[$item['row']][] = $this->issue($item['row'], $profile->match[0] ?? '', 'ambiguous', 'This row matches more than one record.', $item['original']);

                continue;
            }

            $existing = $matches !== null && $matches->count() === 1 ? $matches->first() : null;
            if (! $existing instanceof Model) {
                if ($strategy === 'update_only') {
                    $skipped++;
                    $publicRows[] = $this->publicRow($item['row'], 'skip', $this->label($item), []);

                    continue;
                }

                $payload = $item['supplied'];
                $messages = $this->validate($model, $payload, null);
                if ($messages !== []) {
                    foreach ($messages as $field => $message) {
                        $errors[$item['row']][] = $this->issue($item['row'], $field, 'invalid', $message, $item['original']);
                    }

                    continue;
                }
                if (! $canCreate) {
                    $errors[$item['row']][] = $this->issue($item['row'], '', 'unauthorized', 'You cannot create these records.', $item['original']);

                    continue;
                }

                try {
                    $model->dataClass::from($payload);
                } catch (Throwable) {
                    $errors[$item['row']][] = $this->issue($item['row'], '', 'invalid', 'This row does not match the model.', $item['original']);

                    continue;
                }

                $created++;
                $operations[] = [
                    'op' => 'create',
                    'row' => $item['row'],
                    'attributes' => $payload,
                    'match' => $item['key'],
                    'values' => $item['original'],
                ];
                $publicRows[] = $this->publicRow($item['row'], 'create', $this->label($item), []);

                continue;
            }

            $changes = $this->changes($model, $existing, $item['supplied'], $types);
            if ($changes === []) {
                $unchanged++;
                $publicRows[] = $this->publicRow($item['row'], 'unchanged', $this->label($item), []);

                continue;
            }
            if ($strategy === 'create_only') {
                $skipped++;
                $publicRows[] = $this->publicRow($item['row'], 'skip', $this->label($item), []);

                continue;
            }

            $candidate = array_merge($this->current($existing), $item['supplied']);
            $messages = $this->validate($model, $candidate, $existing);
            if ($messages !== []) {
                foreach ($messages as $field => $message) {
                    $errors[$item['row']][] = $this->issue($item['row'], $field, 'invalid', $message, $item['original']);
                }

                continue;
            }
            if (! $canUpdate) {
                $errors[$item['row']][] = $this->issue($item['row'], '', 'unauthorized', 'You cannot update these records.', $item['original']);

                continue;
            }

            $updated++;
            $diff = [];
            $shown = [];
            foreach ($changes as $field => $change) {
                $diff[$field] = $change['value'];
                $shown[] = ['field' => $field, 'from' => $change['from'], 'to' => $change['to']];
            }
            $operations[] = [
                'op' => 'update',
                'row' => $item['row'],
                'id' => $existing->getKey(),
                'attributes' => $diff,
                'match' => $item['key'],
                'values' => $item['original'],
            ];
            $publicRows[] = $this->publicRow($item['row'], 'update', $this->label($item), $shown);
        }

        ksort($errors);
        $errorList = [];
        foreach ($errors as $issues) {
            foreach ($issues as $issue) {
                $errorList[] = $issue;
            }
        }

        $invalid = count($errors);

        return [
            'valid' => $created + $updated + $unchanged,
            'invalid' => $invalid,
            'skipped' => $skipped,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'failed' => $invalid,
            'strategy' => $strategy,
            'policy' => $policy,
            'partial' => $profile->partial,
            'confirm_partial' => $confirmPartial,
            'errors' => $errorList,
            'rows' => $publicRows,
            'operations' => $operations,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    public function commit(TransferModel $model, array $plan): array
    {
        $policy = (string) ($plan['policy'] ?? 'strict');
        $confirm = (bool) ($plan['confirm_partial'] ?? false);

        if (($plan['errors'][0]['code'] ?? '') === 'strategy' || ($plan['errors'][0]['code'] ?? '') === 'policy') {
            return $this->notSaved($plan);
        }

        if ($plan['invalid'] > 0 && $policy === 'strict') {
            return $this->notSaved($plan);
        }

        if ($plan['invalid'] > 0 && $policy === 'valid_rows' && ! $confirm) {
            $plan['errors'][] = $this->issue(0, '', 'confirmation', 'Confirm that invalid rows will be left out.', []);
            $plan['invalid']++;
            $plan['failed'] = $plan['invalid'];

            return $this->notSaved($plan);
        }

        if ($plan['created'] === 0 && $plan['updated'] === 0) {
            if ($plan['invalid'] > 0) {
                return $this->notSaved($plan);
            }

            return $this->saved($plan, 0, 0, [], []);
        }

        if ($policy === 'valid_rows') {
            return $this->commitPartial($model, $plan);
        }

        return $this->commitStrict($model, $plan);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private function commitStrict(TransferModel $model, array $plan): array
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($model, $plan): void {
                foreach ($plan['operations'] as $operation) {
                    $this->write($model, $operation);
                }
            });
        } catch (Throwable $exception) {
            if (! $exception instanceof TransferConflict) {
                report($exception);
            }

            return [
                'saved' => false,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'skipped' => $plan['skipped'],
                'failed' => 1,
                'valid' => 0,
                'invalid' => 1,
                'strategy' => $plan['strategy'],
                'policy' => $plan['policy'],
                'errors' => [$this->issue(0, '', 'conflict', $exception instanceof TransferConflict ? $exception->getMessage() : 'The import was not saved.', [])],
                'rows' => [],
                'results' => [],
            ];
        }

        return $this->saved($plan, $plan['created'], $plan['updated'], $plan['errors'], []);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private function commitPartial(TransferModel $model, array $plan): array
    {
        $created = 0;
        $updated = 0;
        $errors = $plan['errors'];
        $failed = $plan['invalid'];
        $results = [];

        foreach ($plan['operations'] as $operation) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($model, $operation): void {
                    $this->write($model, $operation);
                });
                if ($operation['op'] === 'create') {
                    $created++;
                } else {
                    $updated++;
                }
                $results[] = ['row' => $operation['row'], 'operation' => $operation['op'], 'status' => 'committed'];
            } catch (Throwable $exception) {
                if (! $exception instanceof TransferConflict) {
                    report($exception);
                }
                $failed++;
                $errors[] = $this->issue(
                    (int) $operation['row'],
                    '',
                    'conflict',
                    $exception instanceof TransferConflict ? $exception->getMessage() : 'This row was not saved.',
                    is_array($operation['values'] ?? null) ? $operation['values'] : [],
                );
                $results[] = ['row' => $operation['row'], 'operation' => $operation['op'], 'status' => 'failed'];
            }
        }

        $saved = $created > 0 || $updated > 0 || ($failed === 0 && $plan['unchanged'] > 0);

        return [
            'saved' => $saved,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $plan['unchanged'],
            'skipped' => $plan['skipped'],
            'failed' => $failed,
            'valid' => $created + $updated + $plan['unchanged'],
            'invalid' => $plan['invalid'] + ($failed - $plan['invalid']),
            'strategy' => $plan['strategy'],
            'policy' => $plan['policy'],
            'errors' => $errors,
            'rows' => $plan['rows'],
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function write(TransferModel $model, array $operation): void
    {
        $key = $operation['match'] ?? null;
        $matches = is_array($key) ? $this->findMatches($model, $key) : collect();

        if ($operation['op'] === 'create') {
            if ($matches->count() !== 0) {
                throw new TransferConflict('This row no longer matches the database. Check the file again before importing.');
            }
            $model->createAction::make()->execute($model->dataClass::from($operation['attributes']));

            return;
        }

        $id = (int) ($operation['id'] ?? 0);
        if ($matches->count() !== 1 || (int) $matches->first()->getKey() !== $id) {
            throw new TransferConflict('This row no longer matches the database. Check the file again before importing.');
        }

        /** @var Model $record */
        $record = $matches->first();
        $record->fill($operation['attributes']);
        $record->save();
    }

    /**
     * @param  array<string, mixed>  $keyValues
     * @return \Illuminate\Support\Collection<int, Model>
     */
    private function findMatches(TransferModel $model, array $keyValues): \Illuminate\Support\Collection
    {
        $query = $model->modelClass::query();
        $types = $this->types($model);

        foreach ($model->profile->match as $field) {
            if (! array_key_exists($field, $keyValues) || ! preg_match('/^[A-Za-z0-9_]+$/', $field)) {
                return collect();
            }
            $value = $keyValues[$field];
            $type = $types[$field] ?? 'string';
            if ($type === 'integer' || $type === 'number') {
                $query->where($field, $value);

                continue;
            }
            $wrapped = $query->getQuery()->grammar->wrap($field);
            $query->whereRaw('lower(trim('.$wrapped.')) = ?', [mb_strtolower(trim((string) $value))]);
        }

        return $query->limit(2)->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function validate(TransferModel $model, array $payload, ?Model $existing): array
    {
        $validator = Validator::make($payload, $model->rulesFor($payload, $existing));
        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->errors()->messages() as $field => $items) {
                $messages[(string) $field] = (string) ($items[0] ?? 'This value is not valid.');
            }

            return $messages;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $supplied
     * @param  array<string, string>  $types
     * @return array<string, array{value: mixed, from: string, to: string}>
     */
    private function changes(TransferModel $model, Model $existing, array $supplied, array $types): array
    {
        $changes = [];
        foreach ($supplied as $field => $value) {
            if (! in_array($field, $existing->getFillable(), true)) {
                continue;
            }
            $current = $this->comparable($existing->getAttribute($field));
            $next = $this->comparable($value);
            if ($this->same($types[$field] ?? 'string', $current, $next)) {
                continue;
            }
            $changes[$field] = [
                'value' => $value,
                'from' => $this->display($current),
                'to' => $this->display($value),
            ];
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    private function current(Model $existing): array
    {
        $values = [];
        foreach ($existing->getFillable() as $field) {
            $values[$field] = $this->comparable($existing->getAttribute($field));
        }

        return $values;
    }

    private function comparable(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    private function same(string $type, mixed $current, mixed $incoming): bool
    {
        if ($type === 'boolean') {
            return (bool) $current === (bool) $incoming;
        }
        if ($type === 'number') {
            if ($current === null || $incoming === null) {
                return $current === null && $incoming === null;
            }

            return abs((float) $current - (float) $incoming) < 0.0000001;
        }
        if ($type === 'integer') {
            return (int) $current === (int) $incoming;
        }
        if ($type === 'array') {
            return json_encode($current) === json_encode($incoming);
        }

        $left = $current === null ? '' : trim((string) $current);
        $right = $incoming === null ? '' : trim((string) $incoming);

        return $left === $right;
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, mixed>  $supplied
     * @return array<string, mixed>|null
     */
    private function keyValues(array $match, array $supplied): ?array
    {
        if ($match === []) {
            return null;
        }
        $values = [];
        foreach ($match as $field) {
            if (! array_key_exists($field, $supplied) || $supplied[$field] === null || $supplied[$field] === '') {
                return null;
            }
            $values[$field] = $supplied[$field];
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $keyValues
     */
    private function fingerprint(array $keyValues): string
    {
        $parts = [];
        foreach ($keyValues as $field => $value) {
            $parts[] = $field.'='.(is_string($value) ? mb_strtolower(trim($value)) : (string) $value);
        }

        return implode('|', $parts);
    }

    /**
     * @return array{state: string, value: mixed}
     */
    private function interpret(string $type, mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        // An empty CSV cell arrives as null because the HTTP layer turns "" into null.
        // The word null in a cell is the explicit clear. A missing column never reaches this method.
        if ($value === null || $value === '') {
            return ['state' => 'empty', 'value' => null];
        }
        if (is_string($value) && mb_strtolower($value) === 'null') {
            return ['state' => 'clear', 'value' => null];
        }

        return ['state' => 'value', 'value' => $this->castValue($type, $value)];
    }

    private function castValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'boolean' => $this->castBoolean($value),
            'integer' => is_numeric($value) ? (int) $value : $value,
            'number' => is_numeric($value) ? (float) $value : $value,
            'array' => $this->castList($value),
            'date' => $this->castDate($value),
            default => is_scalar($value) ? trim((string) $value) : $value,
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
     * @return array<string, string>
     */
    private function types(TransferModel $model): array
    {
        $types = [];
        foreach ($model->fields() as $field) {
            $types[$field['id']] = $field['type'];
        }

        return $types;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function original(array $row): array
    {
        $values = [];
        foreach ($row as $key => $value) {
            if (is_array($value)) {
                $values[$key] = implode(', ', $value);

                continue;
            }
            $values[$key] = trim((string) ($value ?? ''));
        }

        return $values;
    }

    /**
     * @param  array{supplied: array<string, mixed>}  $item
     */
    private function label(array $item): string
    {
        foreach (['name', 'employee_number', 'code'] as $field) {
            if (isset($item['supplied'][$field]) && is_scalar($item['supplied'][$field])) {
                return trim((string) $item['supplied'][$field]);
            }
        }

        return '';
    }

    /**
     * @param  list<array{field: string, from: string, to: string}>  $changes
     * @return array{row: int, operation: string, label: string, changes: list<array{field: string, from: string, to: string}>}
     */
    private function publicRow(int $row, string $operation, string $label, array $changes): array
    {
        return [
            'row' => $row,
            'operation' => $operation,
            'label' => $label,
            'changes' => $changes,
        ];
    }

    private function display(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            $text = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');

            return $text === '' ? '0' : $text;
        }

        return trim((string) $value);
    }

    /**
     * @param  array<string, string>  $values
     * @return array{row: int, field: string, code: string, messages: list<string>, values: array<string, string>}
     */
    private function issue(int $row, string $field, string $code, string $message, array $values): array
    {
        return [
            'row' => $row,
            'field' => $field,
            'code' => $code,
            'messages' => [$message],
            'values' => $values,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rejected(string $code, string $message): array
    {
        return [
            'valid' => 0,
            'invalid' => 1,
            'skipped' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 1,
            'strategy' => '',
            'policy' => '',
            'partial' => false,
            'errors' => [$this->issue(0, '', $code, $message, [])],
            'rows' => [],
            'operations' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private function notSaved(array $plan): array
    {
        return [
            'saved' => false,
            'created' => 0,
            'updated' => 0,
            'unchanged' => $plan['unchanged'] ?? 0,
            'skipped' => $plan['skipped'] ?? 0,
            'failed' => $plan['invalid'] ?? 0,
            'valid' => $plan['valid'] ?? 0,
            'invalid' => $plan['invalid'] ?? 0,
            'strategy' => $plan['strategy'] ?? '',
            'policy' => $plan['policy'] ?? '',
            'errors' => $plan['errors'] ?? [],
            'rows' => $plan['rows'] ?? [],
            'results' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  list<array<string, mixed>>  $errors
     * @param  list<array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function saved(array $plan, int $created, int $updated, array $errors, array $results): array
    {
        return [
            'saved' => true,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $plan['unchanged'],
            'skipped' => $plan['skipped'],
            'failed' => 0,
            'valid' => $created + $updated + $plan['unchanged'],
            'invalid' => 0,
            'strategy' => $plan['strategy'],
            'policy' => $plan['policy'],
            'errors' => $errors,
            'rows' => $plan['rows'],
            'results' => $results,
        ];
    }
}
