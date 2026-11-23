<?php

namespace Domain\Employees\Support;

use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Matches each row on employee number.
 * A new number creates a person. The same number updates Hanab fields that are present in the file.
 */
final class EmployeeImportInspector
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     valid: int,
     *     invalid: int,
     *     skipped: int,
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     errors: list<array{row: int, messages: list<string>}>,
     *     operations: list<array{op: string, id?: int, attributes?: array<string, mixed>}>
     * }
     */
    public function inspect(array $rows): array
    {
        $known = EmployeeImportMatrix::FIELDS;
        $errors = [];
        $skipped = 0;
        $prepared = [];

        $presentKeys = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $presentKeys = [...$presentKeys, ...array_keys($row)];
            }
        }
        $presentKeys = array_values(array_unique($presentKeys));

        if (! in_array('employee_number', $presentKeys, true)) {
            return $this->report(0, 0, count($rows), 0, 0, 0, [
                ['row' => 0, 'messages' => ['Map the employee number column. It identifies each person.']],
            ], []);
        }

        foreach (array_values($rows) as $index => $row) {
            $rowNumber = $index + 1;
            if (! is_array($row)) {
                $errors[$rowNumber][] = 'This row is not a record.';

                continue;
            }

            $unknown = array_values(array_diff(array_keys($row), array_keys($known)));
            if ($unknown !== []) {
                $errors[$rowNumber][] = 'Unknown field: '.implode(', ', $unknown);

                continue;
            }

            $cast = [];
            foreach ($row as $key => $value) {
                $cast[$key] = $this->blankToNull($value);
            }
            if ($this->isEmpty($cast)) {
                $skipped++;

                continue;
            }

            $number = trim((string) ($cast['employee_number'] ?? ''));
            if ($number === '') {
                $errors[$rowNumber][] = 'Employee number is required.';

                continue;
            }

            $prepared[] = [
                'row' => $rowNumber,
                'number' => $number,
                'key' => mb_strtolower($number),
                'values' => $cast,
            ];
        }

        $numbers = [];
        $emails = [];
        foreach ($prepared as $item) {
            $numbers[$item['key']][] = $item['row'];
            $email = $this->emailOf($item['values']);
            if ($email !== null) {
                $emails[$email][] = $item['row'];
            }
        }
        foreach ($numbers as $rowsWithNumber) {
            if (count($rowsWithNumber) < 2) {
                continue;
            }
            foreach ($rowsWithNumber as $rowNumber) {
                $errors[$rowNumber][] = 'This employee number appears more than once in the file.';
            }
        }
        foreach ($emails as $rowsWithEmail) {
            if (count($rowsWithEmail) < 2) {
                continue;
            }
            foreach ($rowsWithEmail as $rowNumber) {
                $errors[$rowNumber][] = 'This email appears more than once in the file.';
            }
        }

        $roles = $this->lookup(EmployeeRole::query()->get(['id', 'name']));
        $locations = $this->lookup(EmployeeLocation::query()->get(['id', 'name']));
        $existing = Employee::query()->get()->keyBy(fn (Employee $employee) => mb_strtolower(trim($employee->employee_number)));
        $emailOwners = [];
        foreach ($existing as $employee) {
            $emailOwners[mb_strtolower($employee->email)] = mb_strtolower(trim($employee->employee_number));
        }

        $operations = [];
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $pending = [];
        $numbersInFile = [];
        foreach ($prepared as $item) {
            $numbersInFile[$item['key']] = $item['number'];
        }
        $numberById = [];
        foreach ($existing as $employee) {
            $numberById[$employee->id] = mb_strtolower(trim($employee->employee_number));
        }

        foreach ($prepared as $item) {
            if (isset($errors[$item['row']])) {
                continue;
            }

            $messages = [];
            $attributes = [];
            $employee = $existing->get($item['key']);
            $values = $item['values'];

            if (array_key_exists('role', $values)) {
                $resolved = $this->resolveName($roles, $values['role'], 'Role');
                if (is_string($resolved)) {
                    $messages[] = $resolved;
                } elseif ($resolved !== null) {
                    $attributes['employee_role_id'] = $resolved;
                }
            } elseif ($employee === null) {
                $messages[] = 'Role is required for a new employee.';
            }

            if (array_key_exists('location', $values)) {
                $resolved = $this->resolveName($locations, $values['location'], 'Location');
                if (is_string($resolved)) {
                    $messages[] = $resolved;
                } elseif ($resolved !== null) {
                    $attributes['employee_location_id'] = $resolved;
                }
            } elseif ($employee === null) {
                $messages[] = 'Location is required for a new employee.';
            }

            foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'employment_type' => 'Employment type', 'is_active' => 'Active'] as $column => $label) {
                if (! array_key_exists($column, $values)) {
                    if ($employee === null && EmployeeImportMatrix::FIELDS[$column]['required_on_create']) {
                        $messages[] = "{$label} is required for a new employee.";
                    }

                    continue;
                }
                if ($values[$column] === null || $values[$column] === '') {
                    $messages[] = "{$label} is required.";
                }
            }

            if (array_key_exists('email', $values) && is_string($values['email']) && $values['email'] !== '') {
                if (! filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $messages[] = 'Email must be a valid email address.';
                } else {
                    $emailKey = mb_strtolower($values['email']);
                    $owner = $emailOwners[$emailKey] ?? null;
                    if ($owner !== null && $owner !== $item['key']) {
                        $messages[] = 'This email belongs to another employee number.';
                    }
                    $attributes['email'] = $values['email'];
                }
            }

            if (array_key_exists('employment_type', $values) && $values['employment_type'] !== null && $values['employment_type'] !== '') {
                $type = mb_strtolower(trim((string) $values['employment_type']));
                if (! in_array($type, ['internal', 'external'], true)) {
                    $messages[] = 'Employment type must be internal or external.';
                } else {
                    $attributes['employment_type'] = $type;
                }
            }

            if (array_key_exists('is_active', $values) && $values['is_active'] !== null && $values['is_active'] !== '') {
                $active = $this->castBoolean($values['is_active']);
                if ($active === null) {
                    $messages[] = 'Active must be true or false.';
                } else {
                    $attributes['is_active'] = $active;
                }
            }

            if (array_key_exists('phone', $values)) {
                $attributes['phone'] = $values['phone'] === null ? null : trim((string) $values['phone']);
            }

            if (array_key_exists('business_email', $values)) {
                $businessEmail = $values['business_email'] === null ? null : trim((string) $values['business_email']);
                if (is_string($businessEmail) && $businessEmail !== '' && ! filter_var($businessEmail, FILTER_VALIDATE_EMAIL)) {
                    $messages[] = 'Business email must be a valid email address.';
                } else {
                    $attributes['business_email'] = $businessEmail === '' ? null : $businessEmail;
                }
            }

            if (array_key_exists('business_phone', $values)) {
                $attributes['business_phone'] = $values['business_phone'] === null ? null : trim((string) $values['business_phone']);
            }

            if (array_key_exists('initials', $values)) {
                $initials = $values['initials'] === null ? null : trim((string) $values['initials']);
                if (is_string($initials) && mb_strlen($initials) > 32) {
                    $messages[] = 'Initials may not be longer than 32 characters.';
                } else {
                    $attributes['initials'] = $initials;
                }
            }

            if (array_key_exists('start_date', $values)) {
                if ($values['start_date'] === null || $values['start_date'] === '') {
                    $attributes['start_date'] = null;
                } else {
                    $date = $this->castDate($values['start_date']);
                    if ($date === null) {
                        $messages[] = 'Start date must be a date.';
                    } else {
                        $attributes['start_date'] = $date;
                    }
                }
            }

            foreach (['first_name', 'last_name'] as $column) {
                if (array_key_exists($column, $values) && is_string($values[$column]) && $values[$column] !== '') {
                    $attributes[$column] = trim($values[$column]);
                }
            }

            if ($messages !== []) {
                $errors[$item['row']] = [...($errors[$item['row']] ?? []), ...$messages];

                continue;
            }

            $manager = ['set' => false, 'number' => null];
            if (array_key_exists('manager_employee_number', $values)) {
                $managerNumber = $values['manager_employee_number'];
                $managerNumber = $managerNumber === null ? null : mb_strtolower(trim((string) $managerNumber));
                if ($managerNumber === '') {
                    $managerNumber = null;
                }
                if ($managerNumber !== null && $managerNumber === $item['key']) {
                    $errors[$item['row']][] = 'An employee cannot report to themselves.';

                    continue;
                }
                if ($managerNumber !== null && ! isset($numbersInFile[$managerNumber]) && ! $existing->has($managerNumber)) {
                    $errors[$item['row']][] = 'Unknown manager employee number.';

                    continue;
                }
                $manager = ['set' => true, 'number' => $managerNumber];
            }

            $pending[] = [
                'item' => $item,
                'employee' => $employee,
                'attributes' => $attributes,
                'manager' => $manager,
            ];
        }

        $graph = [];
        foreach ($existing as $key => $employee) {
            $managerId = $employee->manager_id;
            $graph[$key] = $managerId === null ? null : ($numberById[$managerId] ?? null);
        }
        foreach ($pending as $entry) {
            $graph[$entry['item']['key']] = $entry['manager']['set']
                ? $entry['manager']['number']
                : ($graph[$entry['item']['key']] ?? null);
        }
        foreach ($pending as $index => $entry) {
            if (! $entry['manager']['set'] || $entry['manager']['number'] === null) {
                continue;
            }
            if ($this->reportingLoops($graph, $entry['item']['key'])) {
                $errors[$entry['item']['row']][] = 'This reporting line would loop back to the same employee.';
                unset($pending[$index]);
            }
        }

        foreach ($pending as $entry) {
            if (isset($errors[$entry['item']['row']])) {
                continue;
            }

            $item = $entry['item'];
            $employee = $entry['employee'];
            $attributes = $entry['attributes'];
            $managerNumber = null;
            $managerChanges = false;
            if ($entry['manager']['set']) {
                $currentNumber = null;
                if ($employee instanceof Employee && $employee->manager_id !== null) {
                    $currentNumber = $numberById[$employee->manager_id] ?? null;
                }
                $managerNumber = $entry['manager']['number'];
                $managerChanges = $employee === null || $currentNumber !== $managerNumber;
            }

            if ($employee === null) {
                $attributes['employee_number'] = $item['number'];
                $created++;
                $operation = ['op' => 'create', 'row' => $item['row'], 'number' => $item['key'], 'label' => $item['number'], 'attributes' => $attributes];
                if ($managerChanges) {
                    $operation['manager_number'] = $managerNumber;
                }
                $operations[] = $operation;

                continue;
            }

            $changes = $this->changes($employee, $attributes);
            if ($changes === [] && ! $managerChanges) {
                $unchanged++;
                $operations[] = ['op' => 'unchanged', 'row' => $item['row'], 'id' => $employee->id, 'number' => $item['key']];

                continue;
            }

            $updated++;
            $operation = ['op' => 'update', 'row' => $item['row'], 'id' => $employee->id, 'number' => $item['key'], 'label' => $item['number'], 'attributes' => $changes, 'previous' => $this->previousValues($employee, $changes)];
            if ($managerChanges) {
                $operation['manager_number'] = $managerNumber;
            }
            $operations[] = $operation;
        }

        ksort($errors);
        $errorList = [];
        foreach ($errors as $rowNumber => $messages) {
            $errorList[] = ['row' => $rowNumber, 'messages' => array_values(array_unique($messages))];
        }

        return $this->report(
            $created + $updated + $unchanged,
            count($errors),
            $skipped,
            $created,
            $updated,
            $unchanged,
            $errorList,
            $operations,
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EmployeeRole|EmployeeLocation>  $records
     * @return array<string, int|false>
     */
    private function lookup($records): array
    {
        $lookup = [];
        foreach ($records as $record) {
            $key = mb_strtolower(trim((string) $record->name));
            $lookup[$key] = array_key_exists($key, $lookup) ? false : $record->id;
        }

        return $lookup;
    }

    /**
     * @param  array<string, int|false>  $lookup
     */
    private function resolveName(array $lookup, mixed $value, string $label): int|string|null
    {
        $name = trim((string) $value);
        if ($name === '') {
            return "{$label} is required.";
        }
        $match = $lookup[mb_strtolower($name)] ?? null;
        if ($match === null) {
            return "Unknown {$label}.";
        }
        if ($match === false) {
            return "{$label} matches more than one record.";
        }

        return $match;
    }

    /**
     * @param  array<string, string|null>  $graph
     */
    private function reportingLoops(array $graph, string $start): bool
    {
        $seen = [];
        $current = $graph[$start] ?? null;

        for ($step = 0; $step < 50 && is_string($current) && $current !== ''; $step++) {
            if ($current === $start || isset($seen[$current])) {
                return true;
            }
            $seen[$current] = true;
            $current = $graph[$current] ?? null;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function changes(Employee $employee, array $attributes): array
    {
        $changes = [];
        foreach ($attributes as $key => $value) {
            $current = $employee->getAttribute($key);
            if ($key === 'start_date') {
                $current = $current instanceof Carbon ? $current->toDateString() : ($current === null ? null : (string) $current);
            }
            if ($key === 'is_active') {
                $current = (bool) $current;
            }
            if ($key === 'phone' || $key === 'business_phone' || $key === 'business_email') {
                $current = $current === null ? null : (string) $current;
            }
            if ($current != $value) {
                $changes[$key] = $value;
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, string>
     */
    private function previousValues(Employee $employee, array $changes): array
    {
        $previous = [];
        foreach (array_keys($changes) as $key) {
            $current = $employee->getAttribute($key);
            if ($current instanceof Carbon) {
                $previous[$key] = $current->toDateString();
            } elseif (is_bool($current)) {
                $previous[$key] = $current ? 'true' : 'false';
            } else {
                $previous[$key] = (string) ($current ?? '');
            }
        }

        return $previous;
    }

    private function emailOf(array $values): ?string
    {
        if (! isset($values['email']) || ! is_string($values['email']) || trim($values['email']) === '') {
            return null;
        }

        return mb_strtolower(trim($values['email']));
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }

    private function castBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $text = mb_strtolower(trim((string) $value));

        return match ($text) {
            '1', 'true', 'yes', 'y', 'ja', 'waar', 'active', 'actief' => true,
            '0', 'false', 'no', 'n', 'nee', 'onwaar', 'left', 'inactive', 'inactief' => false,
            default => null,
        };
    }

    private function castDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial > 20000 && $serial < 80000) {
                return gmdate('Y-m-d', (int) round(($serial - 25569) * 86400));
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{row: int, messages: list<string>}>  $errors
     * @param  list<array{op: string, id?: int, attributes?: array<string, mixed>}>  $operations
     * @return array{
     *     valid: int,
     *     invalid: int,
     *     skipped: int,
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     errors: list<array{row: int, messages: list<string>}>,
     *     operations: list<array{op: string, id?: int, attributes?: array<string, mixed>}>
     * }
     */
    private function report(int $valid, int $invalid, int $skipped, int $created, int $updated, int $unchanged, array $errors, array $operations): array
    {
        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'skipped' => $skipped,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'errors' => $errors,
            'operations' => $operations,
        ];
    }
}
