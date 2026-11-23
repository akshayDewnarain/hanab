<?php

namespace Domain\Employees\Actions;

use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeImportLog;
use Domain\Employees\Support\EmployeeImportInspector;
use Domain\Employees\Support\EmployeeImportStrategy;
use Domain\Messages\Actions\DraftEmployeeIntroductionAction;
use Domain\Support\Actions\BaseAction;
use Illuminate\Support\Facades\DB;
use Throwable;

class CommitEmployeeImportAction extends BaseAction
{
    /**
     * Writes the file only when every row passes.
     * Photo, skills, certificates, and notification preferences are left as they are.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     saved: bool,
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     valid: int,
     *     invalid: int,
     *     skipped: int,
     *     errors: list<array{row: int, messages: list<string>}>
     * }
     */
    public function execute(array $rows, ?string $fileName = null, ?int $userId = null, string $strategy = 'upsert', string $policy = 'strict'): array
    {
        $fileName = $fileName !== null && trim($fileName) !== '' ? trim($fileName) : null;

        if ($policy !== 'strict') {
            $result = $this->result(false, [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'valid' => 0,
                'invalid' => 1,
                'skipped' => 0,
            ], [[
                'row' => 0,
                'messages' => ['Employee import keeps every row in one transaction. Invalid rows are not skipped.'],
            ]]);
            $result['failed'] = 1;
            $result['errors'][0]['code'] = 'policy';
            $this->log($userId, $fileName, 'rejected', $result);

            return $result;
        }

        $report = EmployeeImportStrategy::adjust((new EmployeeImportInspector)->inspect($rows), $strategy);
        if (($report['errors'][0]['code'] ?? '') === 'strategy') {
            $result = $this->result(false, $report, $report['errors']);
            $this->log($userId, $fileName, 'rejected', $result);

            return $result;
        }

        if ($report['invalid'] > 0) {
            $result = $this->result(false, $report, $report['errors'] !== []
                ? $report['errors']
                : [['row' => 0, 'messages' => ['Nothing to import.']]]);
            $this->log($userId, $fileName, 'rejected', $result);

            return $result;
        }

        if ($report['created'] === 0 && $report['updated'] === 0) {
            $result = $this->result(true, $report, []);
            $this->log($userId, $fileName, 'committed', $result);

            return $result;
        }

        try {
            DB::transaction(function () use ($report, $userId): void {
                foreach ($report['operations'] as $operation) {
                    if ($operation['op'] === 'create') {
                        $employee = CreateEmployeeAction::make()->execute(EmployeeData::from($operation['attributes']));
                        if ($employee->is_active) {
                            DraftEmployeeIntroductionAction::make()->execute($employee, $userId);
                        }
                    }
                    if ($operation['op'] === 'update') {
                        $employee = Employee::query()->findOrFail($operation['id']);
                        $employee->fill($operation['attributes']);
                        $employee->save();
                    }
                }

                $byNumber = Employee::query()->get()->keyBy(
                    fn (Employee $employee): string => mb_strtolower(trim($employee->employee_number)),
                );
                foreach ($report['operations'] as $operation) {
                    if (! array_key_exists('manager_number', $operation)) {
                        continue;
                    }
                    $employee = $byNumber->get($operation['number']);
                    if (! $employee instanceof Employee) {
                        throw new \RuntimeException('Imported employee is missing.');
                    }
                    $managerId = null;
                    if ($operation['manager_number'] !== null) {
                        $manager = $byNumber->get($operation['manager_number']);
                        if (! $manager instanceof Employee) {
                            throw new \RuntimeException('Imported manager is missing.');
                        }
                        $managerId = $manager->id;
                    }
                    if ($employee->manager_id !== $managerId) {
                        $employee->manager_id = $managerId;
                        $employee->save();
                    }
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $result = $this->result(false, [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'valid' => 0,
                'invalid' => 1,
                'skipped' => $report['skipped'],
            ], [['row' => 0, 'messages' => ['The import was not saved.']]]);
            $this->log($userId, $fileName, 'rejected', $result);

            return $result;
        }

        $result = $this->result(true, $report, []);
        $this->log($userId, $fileName, 'committed', $result);

        return $result;
    }

    /**
     * @param  array{created: int, updated: int, unchanged: int, valid: int, invalid: int, skipped: int}  $report
     * @param  list<array{row: int, messages: list<string>}>  $errors
     * @return array{saved: bool, created: int, updated: int, unchanged: int, valid: int, invalid: int, skipped: int, errors: list<array{row: int, messages: list<string>}>}
     */
    private function result(bool $saved, array $report, array $errors): array
    {
        return [
            'saved' => $saved,
            'created' => $report['created'],
            'updated' => $report['updated'],
            'unchanged' => $report['unchanged'],
            'valid' => $report['valid'],
            'invalid' => $report['invalid'],
            'skipped' => $report['skipped'],
            'failed' => $saved ? 0 : ($report['invalid'] ?? 0),
            'errors' => $errors,
        ];
    }

    /**
     * @param  array{created: int, updated: int, unchanged: int, skipped: int, invalid: int}  $result
     */
    private function log(?int $userId, ?string $fileName, string $outcome, array $result): void
    {
        EmployeeImportLog::query()->create([
            'user_id' => $userId,
            'file_name' => $fileName,
            'outcome' => $outcome,
            'created_count' => $result['created'],
            'updated_count' => $result['updated'],
            'unchanged_count' => $result['unchanged'],
            'skipped_count' => $result['skipped'],
            'error_count' => $result['invalid'],
        ]);
    }
}
