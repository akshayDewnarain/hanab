<?php

namespace Domain\Employees\Actions;

use Domain\Employees\Support\EmployeeImportInspector;
use Domain\Employees\Support\EmployeeImportStrategy;
use Domain\Support\Actions\BaseAction;

class PreviewEmployeeImportAction extends BaseAction
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{valid: int, invalid: int, skipped: int, created: int, updated: int, unchanged: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function execute(array $rows, string $strategy = 'upsert', string $policy = 'strict'): array
    {
        if ($policy !== 'strict') {
            return EmployeeImportStrategy::rejected('policy', 'Employee import keeps every row in one transaction. Invalid rows are not skipped.');
        }

        $report = EmployeeImportStrategy::adjust((new EmployeeImportInspector)->inspect($rows), $strategy);
        if (($report['errors'][0]['code'] ?? '') === 'strategy') {
            return $report;
        }
        $creates = [];
        $updates = [];

        foreach ($report['operations'] as $operation) {
            if (($operation['op'] ?? '') === 'create') {
                $creates[] = [
                    'row' => (int) ($operation['row'] ?? 0),
                    'employee_number' => (string) ($operation['label'] ?? $operation['number'] ?? ''),
                ];
            }
            if (($operation['op'] ?? '') === 'update') {
                $fields = array_keys($operation['attributes'] ?? []);
                if (array_key_exists('manager_number', $operation)) {
                    $fields[] = 'manager_employee_number';
                }
                $changes = [];
                foreach (array_values(array_unique($fields)) as $field) {
                    $changes[] = [
                        'field' => $field,
                        'from' => (string) ($operation['previous'][$field] ?? ''),
                        'to' => (string) ($operation['attributes'][$field] ?? ($field === 'manager_employee_number' ? ($operation['manager_number'] ?? '') : '')),
                    ];
                }
                $updates[] = [
                    'row' => (int) ($operation['row'] ?? 0),
                    'employee_number' => (string) ($operation['label'] ?? $operation['number'] ?? ''),
                    'fields' => array_values(array_unique($fields)),
                    'changes' => $changes,
                ];
            }
        }

        unset($report['operations']);
        $report['creates'] = $creates;
        $report['updates'] = $updates;

        return $report;
    }
}
