<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateOrganizationLevelRequest;
use Application\API\Employees\Requests\QueryBuilder\OrganizationLevelQueryRequest;
use Application\API\Employees\Requests\UpdateOrganizationLevelRequest;
use Application\API\Employees\Resources\OrganizationLevelResource;
use Application\API\Employees\Resources\OrganizationLevelResourceCollection;
use Domain\Auth\Services\PermissionService;
use Domain\Employees\Actions\CreateOrganizationLevelAction;
use Domain\Employees\Actions\DeleteOrganizationLevelAction;
use Domain\Employees\Actions\UpdateOrganizationLevelAction;
use Domain\Employees\DataTransferObjects\OrganizationLevelData;
use Domain\Employees\Helpers\OrganizationLevelHelper;
use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class OrganizationLevelsController extends BaseAPICrudController
{
    public function index(OrganizationLevelQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateOrganizationLevelRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(OrganizationLevel $organizationLevel): JsonResponse
    {
        return $this->handleShow($organizationLevel);
    }

    public function update(UpdateOrganizationLevelRequest $request, OrganizationLevel $organizationLevel): JsonResponse
    {
        return $this->handleUpdate($request, $organizationLevel);
    }

    public function destroy(OrganizationLevel $organizationLevel): JsonResponse
    {
        return $this->handleDestroy($organizationLevel);
    }

    protected function permissions(): array
    {
        return [
            'create' => PermissionService::createPermission(OrganizationLevel::class),
            'update' => PermissionService::updatePermission(OrganizationLevel::class),
            'delete' => PermissionService::deletePermission(OrganizationLevel::class),
        ];
    }

    protected function modelName(): string
    {
        return 'organization-levels';
    }

    protected function routeModelParameter(): string
    {
        return 'organization_level';
    }

    protected function model(): mixed
    {
        return OrganizationLevel::class;
    }

    protected function helper(): mixed
    {
        return OrganizationLevelHelper::class;
    }

    protected function dto(): mixed
    {
        return OrganizationLevelData::class;
    }

    protected function resource(): mixed
    {
        return OrganizationLevelResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return OrganizationLevelResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateOrganizationLevelAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateOrganizationLevelAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteOrganizationLevelAction::class;
    }
}
