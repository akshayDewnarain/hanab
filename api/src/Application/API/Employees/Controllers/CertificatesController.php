<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateCertificateRequest;
use Application\API\Employees\Requests\QueryBuilder\CertificateQueryRequest;
use Application\API\Employees\Requests\UpdateCertificateRequest;
use Application\API\Employees\Resources\CertificateResource;
use Application\API\Employees\Resources\CertificateResourceCollection;
use Domain\Employees\Actions\CreateCertificateAction;
use Domain\Employees\Actions\DeleteCertificateAction;
use Domain\Employees\Actions\UpdateCertificateAction;
use Domain\Employees\DataTransferObjects\CertificateData;
use Domain\Employees\Helpers\CertificateHelper;
use Domain\Employees\Models\Certificate;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class CertificatesController extends BaseAPICrudController
{
    public function index(CertificateQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateCertificateRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(Certificate $certificate): JsonResponse
    {
        return $this->handleShow($certificate);
    }

    public function update(UpdateCertificateRequest $request, Certificate $certificate): JsonResponse
    {
        return $this->handleUpdate($request, $certificate);
    }

    public function destroy(Certificate $certificate): JsonResponse
    {
        return $this->handleDestroy($certificate);
    }

    protected function modelName(): string
    {
        return 'certificates';
    }

    protected function routeModelParameter(): string
    {
        return 'certificate';
    }

    protected function model(): mixed
    {
        return Certificate::class;
    }

    protected function helper(): mixed
    {
        return CertificateHelper::class;
    }

    protected function dto(): mixed
    {
        return CertificateData::class;
    }

    protected function resource(): mixed
    {
        return CertificateResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return CertificateResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateCertificateAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateCertificateAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteCertificateAction::class;
    }
}
