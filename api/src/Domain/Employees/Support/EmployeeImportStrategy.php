<?php

namespace Domain\Employees\Support;

/**
 * Employees stay matched on employee number.
 * The default strategy is the existing upsert. Other strategies only change
 * whether a create or an update is allowed to be written.
 */
final class EmployeeImportStrategy
{
    /**
     * @param  array{
     *     valid: int,
     *     invalid: int,
     *     skipped: int,
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     errors: list<array{row: int, messages: list<string>}>,
     *     operations: list<array<string, mixed>>
     * }  $report
     * @return array<string, mixed>
     */
    public static function adjust(array $report, string $strategy): array
    {
        if (! in_array($strategy, ['create_only', 'upsert', 'update_only'], true)) {
            return self::rejected('strategy', 'Choose create only, create and update, or update only.');
        }

        $operations = [];
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = $report['skipped'];

        foreach ($report['operations'] as $operation) {
            $op = (string) ($operation['op'] ?? '');
            $blocked = ($strategy === 'create_only' && $op === 'update')
                || ($strategy === 'update_only' && $op === 'create');

            if ($blocked) {
                unset($operation['attributes'], $operation['manager_number'], $operation['previous']);
                $operation['op'] = 'skip';
                $skipped++;
                $operations[] = $operation;

                continue;
            }

            $operations[] = $operation;
            if ($op === 'create') {
                $created++;
            } elseif ($op === 'update') {
                $updated++;
            } elseif ($op === 'unchanged') {
                $unchanged++;
            }
        }

        $report['operations'] = $operations;
        $report['created'] = $created;
        $report['updated'] = $updated;
        $report['unchanged'] = $unchanged;
        $report['skipped'] = $skipped;
        $report['valid'] = $created + $updated + $unchanged;
        $report['failed'] = $report['invalid'];
        $report['rows'] = self::rows($operations);
        $report['strategy'] = $strategy;
        $report['policy'] = 'strict';

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rejected(string $code, string $message): array
    {
        return [
            'valid' => 0,
            'invalid' => 1,
            'skipped' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 1,
            'saved' => false,
            'errors' => [[
                'row' => 0,
                'field' => '',
                'code' => $code,
                'messages' => [$message],
                'values' => [],
            ]],
            'operations' => [],
            'rows' => [],
            'creates' => [],
            'updates' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     * @return list<array{row: int, operation: string, label: string, changes: list<array{field: string, from: string, to: string}>}>
     */
    private static function rows(array $operations): array
    {
        $rows = [];
        foreach ($operations as $operation) {
            $op = (string) ($operation['op'] ?? '');
            if ($op === 'skipped') {
                $op = 'skip';
            }
            $changes = [];
            $previous = is_array($operation['previous'] ?? null) ? $operation['previous'] : [];
            $attributes = is_array($operation['attributes'] ?? null) ? $operation['attributes'] : [];
            foreach ($attributes as $field => $value) {
                $changes[] = [
                    'field' => (string) $field,
                    'from' => (string) ($previous[$field] ?? ''),
                    'to' => self::display($value),
                ];
            }
            if (array_key_exists('manager_number', $operation)) {
                $changes[] = [
                    'field' => 'manager_employee_number',
                    'from' => (string) ($previous['manager_employee_number'] ?? ''),
                    'to' => self::display($operation['manager_number']),
                ];
            }
            $rows[] = [
                'row' => (int) ($operation['row'] ?? 0),
                'operation' => $op,
                'label' => (string) ($operation['label'] ?? $operation['number'] ?? ''),
                'changes' => $changes,
            ];
        }

        return $rows;
    }

    private static function display(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return trim((string) $value);
    }
}
