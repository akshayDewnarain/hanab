<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeLocationRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeLocationQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeLocationRequest;
use Application\API\Employees\Resources\EmployeeLocationResource;
use Application\API\Employees\Resources\EmployeeLocationResourceCollection;
use Domain\Employees\Actions\CreateEmployeeLocationAction;
use Domain\Employees\Actions\DeleteEmployeeLocationAction;
use Domain\Employees\Actions\UpdateEmployeeLocationAction;
use Domain\Employees\DataTransferObjects\EmployeeLocationData;
use Domain\Employees\Helpers\EmployeeLocationHelper;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class EmployeeLocationsController extends BaseAPICrudController
{
    public function index(EmployeeLocationQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateEmployeeLocationRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(EmployeeLocation $employee_location): JsonResponse
    {
        return $this->handleShow($employee_location);
    }

    public function update(UpdateEmployeeLocationRequest $request, EmployeeLocation $employee_location): JsonResponse
    {
        return $this->handleUpdate($request, $employee_location);
    }

    public function destroy(EmployeeLocation $employee_location): JsonResponse
    {
        return $this->handleDestroy($employee_location);
    }

    protected function modelName(): string
    {
        return 'employee_locations';
    }

    protected function routeModelParameter(): string
    {
        return 'employee_location';
    }

    protected function model(): mixed
    {
        return EmployeeLocation::class;
    }

    protected function helper(): mixed
    {
        return EmployeeLocationHelper::class;
    }

    protected function dto(): mixed
    {
        return EmployeeLocationData::class;
    }

    protected function resource(): mixed
    {
        return EmployeeLocationResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return EmployeeLocationResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateEmployeeLocationAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateEmployeeLocationAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteEmployeeLocationAction::class;
    }
}
