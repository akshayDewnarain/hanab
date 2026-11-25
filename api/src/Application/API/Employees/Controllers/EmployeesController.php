<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeQueryRequest;
use Application\API\Employees\Requests\QueryBuilder\ExpertiseSearchQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeRequest;
use Application\API\Employees\Resources\EmployeeResource;
use Application\API\Employees\Resources\EmployeeResourceCollection;
use Application\API\Employees\Resources\ExpertiseSearchResultResource;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\Maintainer;
use Domain\Employees\Actions\CreateEmployeeAction;
use Domain\Employees\Actions\DeleteEmployeeAction;
use Domain\Employees\Actions\SearchColleaguesByExpertiseAction;
use Domain\Employees\Actions\SummarizeEmployeesByLocationAction;
use Domain\Employees\Actions\UpdateEmployeeAction;
use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Employees\Models\Employee;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeesController extends BaseAPICrudController
{
    public function index(EmployeeQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function expertiseSearch(ExpertiseSearchQueryRequest $request): JsonResponse
    {
        $results = SearchColleaguesByExpertiseAction::make()->execute($request->validated('query'));

        return ExpertiseSearchResultResource::collection($results)->response();
    }

    public function locations(EmployeeQueryRequest $request): JsonResponse
    {
        return response()->json([
            'data' => SummarizeEmployeesByLocationAction::make()->execute($request->queryBuilder()),
        ]);
    }

    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(Request $request, Employee $employee): JsonResponse
    {
        if (! $employee->is_active && ! Maintainer::allows($request->user(), PermissionService::updatePermission(Employee::class))) {
            abort(404);
        }

        return $this->handleShow($employee, [
            'directReports' => function ($query): void {
                $query->where('is_active', true)
                    ->with(['employeeRole', 'media'])
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        return $this->handleUpdate($request, $employee);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        return $this->handleDestroy($employee);
    }

    protected function permissions(): array
    {
        return [
            'create' => PermissionService::createPermission(Employee::class),
            'update' => PermissionService::updatePermission(Employee::class),
            'delete' => PermissionService::deletePermission(Employee::class),
        ];
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
