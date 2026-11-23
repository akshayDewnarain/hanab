<?php

namespace Domain\Support\Transfer\Actions;

use Application\API\Employees\Requests\QueryBuilder\CertificateQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeCertificateQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeLocationQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeRoleQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeSkillQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\SkillQueryRequest;
use Domain\Employees\Models\Employee;
use Domain\Employees\Support\EmployeeImportMatrix;
use Domain\Support\Actions\BaseAction;
use Domain\Support\Http\Requests\QueryBuilders\BaseModelQueryRequest;
use Domain\Support\Transfer\TransferCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ExportTransferRowsAction extends BaseAction
{
    /**
     * Same filters and search as the model's list. An empty query exports every permitted row.
     *
     * @var array<string, class-string<BaseModelQueryRequest>>
     */
    private const QUERY_REQUESTS = [
        'employees' => EmployeeQueryRequest::class,
        'employee-roles' => EmployeeRoleQueryRequest::class,
        'employee-locations' => EmployeeLocationQueryRequest::class,
        'skills' => SkillQueryRequest::class,
        'certificates' => CertificateQueryRequest::class,
        'employee-skills' => EmployeeSkillQueryRequest::class,
        'employee-certificates' => EmployeeCertificateQueryRequest::class,
    ];

    /**
     * @return array{fields: list<array{id: string, required: bool, type: string}>, rows: list<array<string, string>>}
     */
    public function execute(string $modelKey): array
    {
        $records = $this->records($modelKey);

        if ($modelKey === 'employees') {
            return $this->employees($records);
        }

        $model = (new TransferCatalog)->find($modelKey);
        $fields = $model->fields();
        $keys = array_column($fields, 'id');
        $rows = [];

        foreach ($records as $record) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->exportValue($record->getAttribute($key));
            }
            $rows[] = $row;
        }

        return [
            'fields' => $fields,
            'rows' => $rows,
        ];
    }

    /**
     * @return Collection<int, Model>
     */
    private function records(string $modelKey): Collection
    {
        $requestClass = self::QUERY_REQUESTS[$modelKey] ?? null;
        if ($requestClass === null) {
            $model = (new TransferCatalog)->find($modelKey);

            return $model->modelClass::query()->orderBy('id')->get();
        }

        $form = $requestClass::createFrom(request());
        $form->setContainer(app());
        $form->setRedirector(app('redirect'));

        /** @var Collection<int, Model> $records */
        $records = $form->queryBuilder()->reorder('id')->get();

        return $records;
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array{fields: list<array{id: string, required: bool, type: string, example: string, owner: string}>, rows: list<array<string, string>>}
     */
    private function employees(Collection $records): array
    {
        $fields = EmployeeImportMatrix::fields();
        $rows = [];
        $records->load(['employeeRole', 'employeeLocation', 'manager']);

        foreach ($records as $record) {
            /** @var Employee $employee */
            $employee = $record;
            $rows[] = [
                'employee_number' => (string) $employee->employee_number,
                'first_name' => (string) $employee->first_name,
                'last_name' => (string) $employee->last_name,
                'initials' => (string) ($employee->initials ?? ''),
                'email' => (string) $employee->email,
                'phone' => (string) ($employee->phone ?? ''),
                'role' => (string) ($employee->employeeRole?->name ?? ''),
                'location' => (string) ($employee->employeeLocation?->name ?? ''),
                'employment_type' => (string) $employee->employment_type,
                'start_date' => $employee->start_date instanceof Carbon ? $employee->start_date->toDateString() : (string) ($employee->start_date ?? ''),
                'is_active' => $employee->is_active ? 'true' : 'false',
                'manager_employee_number' => (string) ($employee->manager?->employee_number ?? ''),
            ];
        }

        return [
            'fields' => $fields,
            'rows' => $rows,
        ];
    }

    private function exportValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return implode(', ', array_map(strval(...), $value));
        }

        return (string) $value;
    }
}
