<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateSkillRequest;
use Application\API\Employees\Requests\QueryBuilder\SkillQueryRequest;
use Application\API\Employees\Requests\UpdateSkillRequest;
use Application\API\Employees\Resources\SkillResource;
use Application\API\Employees\Resources\SkillResourceCollection;
use Domain\Employees\Actions\CreateSkillAction;
use Domain\Employees\Actions\DeleteSkillAction;
use Domain\Employees\Actions\UpdateSkillAction;
use Domain\Employees\DataTransferObjects\SkillData;
use Domain\Employees\Helpers\SkillHelper;
use Domain\Employees\Models\Skill;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class SkillsController extends BaseAPICrudController
{
    public function index(SkillQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateSkillRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(Skill $skill): JsonResponse
    {
        return $this->handleShow($skill);
    }

    public function update(UpdateSkillRequest $request, Skill $skill): JsonResponse
    {
        return $this->handleUpdate($request, $skill);
    }

    public function destroy(Skill $skill): JsonResponse
    {
        return $this->handleDestroy($skill);
    }

    protected function modelName(): string
    {
        return 'skills';
    }

    protected function routeModelParameter(): string
    {
        return 'skill';
    }

    protected function model(): mixed
    {
        return Skill::class;
    }

    protected function helper(): mixed
    {
        return SkillHelper::class;
    }

    protected function dto(): mixed
    {
        return SkillData::class;
    }

    protected function resource(): mixed
    {
        return SkillResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return SkillResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateSkillAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateSkillAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteSkillAction::class;
    }
}
