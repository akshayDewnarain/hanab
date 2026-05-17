<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeRequest;
use Application\API\Employees\Resources\EmployeeResource;
use Application\API\Employees\Resources\EmployeeResourceCollection;
use Domain\Employees\Actions\CreateEmployeeAction;
use Domain\Employees\Actions\DeleteEmployeeAction;
use Domain\Employees\Actions\UpdateEmployeeAction;
use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Employees\Models\Employee;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class EmployeesController extends BaseAPICrudController
{
    public function index(EmployeeQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(Employee $employee): JsonResponse
    {
        return $this->handleShow($employee);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        return $this->handleUpdate($request, $employee);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        return $this->handleDestroy($employee);
    }

    protected function modelName(): string
    {
        return 'employees';
    }

    protected function routeModelParameter(): string
    {
        return 'employee';
    }

    protected function model(): mixed
    {
        return Employee::class;
    }

    protected function helper(): mixed
    {
        return EmployeeHelper::class;
    }

    protected function dto(): mixed
    {
        return EmployeeData::class;
    }

    protected function resource(): mixed
    {
        return EmployeeResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return EmployeeResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateEmployeeAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateEmployeeAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteEmployeeAction::class;
    }
}
