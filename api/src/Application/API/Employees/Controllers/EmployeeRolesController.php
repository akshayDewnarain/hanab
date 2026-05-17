<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeRoleRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeRoleQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeRoleRequest;
use Application\API\Employees\Resources\EmployeeRoleResource;
use Application\API\Employees\Resources\EmployeeRoleResourceCollection;
use Domain\Employees\Actions\CreateEmployeeRoleAction;
use Domain\Employees\Actions\DeleteEmployeeRoleAction;
use Domain\Employees\Actions\UpdateEmployeeRoleAction;
use Domain\Employees\DataTransferObjects\EmployeeRoleData;
use Domain\Employees\Helpers\EmployeeRoleHelper;
use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class EmployeeRolesController extends BaseAPICrudController
{
    public function index(EmployeeRoleQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateEmployeeRoleRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(EmployeeRole $employee_role): JsonResponse
    {
        return $this->handleShow($employee_role);
    }

    public function update(UpdateEmployeeRoleRequest $request, EmployeeRole $employee_role): JsonResponse
    {
        return $this->handleUpdate($request, $employee_role);
    }

    public function destroy(EmployeeRole $employee_role): JsonResponse
    {
        return $this->handleDestroy($employee_role);
    }

    protected function modelName(): string
    {
        return 'employee_roles';
    }

    protected function routeModelParameter(): string
    {
        return 'employee_role';
    }

    protected function model(): mixed
    {
        return EmployeeRole::class;
    }

    protected function helper(): mixed
    {
        return EmployeeRoleHelper::class;
    }

    protected function dto(): mixed
    {
        return EmployeeRoleData::class;
    }

    protected function resource(): mixed
    {
        return EmployeeRoleResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return EmployeeRoleResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateEmployeeRoleAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateEmployeeRoleAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteEmployeeRoleAction::class;
    }
}
