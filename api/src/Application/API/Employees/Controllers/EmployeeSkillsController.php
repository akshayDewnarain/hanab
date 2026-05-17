<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeSkillRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeSkillQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeSkillRequest;
use Application\API\Employees\Resources\EmployeeSkillResource;
use Application\API\Employees\Resources\EmployeeSkillResourceCollection;
use Domain\Employees\Actions\CreateEmployeeSkillAction;
use Domain\Employees\Actions\DeleteEmployeeSkillAction;
use Domain\Employees\Actions\UpdateEmployeeSkillAction;
use Domain\Employees\DataTransferObjects\EmployeeSkillData;
use Domain\Employees\Helpers\EmployeeSkillHelper;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class EmployeeSkillsController extends BaseAPICrudController
{
    public function index(EmployeeSkillQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateEmployeeSkillRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(EmployeeSkill $employee_skill): JsonResponse
    {
        return $this->handleShow($employee_skill);
    }

    public function update(UpdateEmployeeSkillRequest $request, EmployeeSkill $employee_skill): JsonResponse
    {
        return $this->handleUpdate($request, $employee_skill);
    }

    public function destroy(EmployeeSkill $employee_skill): JsonResponse
    {
        return $this->handleDestroy($employee_skill);
    }

    protected function modelName(): string
    {
        return 'employee_skills';
    }

    protected function routeModelParameter(): string
    {
        return 'employee_skill';
    }

    protected function model(): mixed
    {
        return EmployeeSkill::class;
    }

    protected function helper(): mixed
    {
        return EmployeeSkillHelper::class;
    }

    protected function dto(): mixed
    {
        return EmployeeSkillData::class;
    }

    protected function resource(): mixed
    {
        return EmployeeSkillResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return EmployeeSkillResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateEmployeeSkillAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateEmployeeSkillAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteEmployeeSkillAction::class;
    }
}
