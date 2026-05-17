<?php

namespace Application\API\Employees\Controllers;

use Application\API\Employees\Requests\CreateEmployeeCertificateRequest;
use Application\API\Employees\Requests\QueryBuilder\EmployeeCertificateQueryRequest;
use Application\API\Employees\Requests\UpdateEmployeeCertificateRequest;
use Application\API\Employees\Resources\EmployeeCertificateResource;
use Application\API\Employees\Resources\EmployeeCertificateResourceCollection;
use Domain\Employees\Actions\CreateEmployeeCertificateAction;
use Domain\Employees\Actions\DeleteEmployeeCertificateAction;
use Domain\Employees\Actions\UpdateEmployeeCertificateAction;
use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\Helpers\EmployeeCertificateHelper;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Support\Http\Controllers\BaseAPICrudController;
use Illuminate\Http\JsonResponse;

class EmployeeCertificatesController extends BaseAPICrudController
{
    public function index(EmployeeCertificateQueryRequest $request): JsonResponse
    {
        return $this->handleIndex($request);
    }

    public function store(CreateEmployeeCertificateRequest $request): JsonResponse
    {
        return $this->handleStore($request);
    }

    public function show(EmployeeCertificate $employee_certificate): JsonResponse
    {
        return $this->handleShow($employee_certificate);
    }

    public function update(UpdateEmployeeCertificateRequest $request, EmployeeCertificate $employee_certificate): JsonResponse
    {
        return $this->handleUpdate($request, $employee_certificate);
    }

    public function destroy(EmployeeCertificate $employee_certificate): JsonResponse
    {
        return $this->handleDestroy($employee_certificate);
    }

    protected function modelName(): string
    {
        return 'employee_certificates';
    }

    protected function routeModelParameter(): string
    {
        return 'employee_certificate';
    }

    protected function model(): mixed
    {
        return EmployeeCertificate::class;
    }

    protected function helper(): mixed
    {
        return EmployeeCertificateHelper::class;
    }

    protected function dto(): mixed
    {
        return EmployeeCertificateData::class;
    }

    protected function resource(): mixed
    {
        return EmployeeCertificateResource::class;
    }

    protected function resourceCollection(): mixed
    {
        return EmployeeCertificateResourceCollection::class;
    }

    protected function createActionClass(): string
    {
        return CreateEmployeeCertificateAction::class;
    }

    protected function updateActionClass(): string
    {
        return UpdateEmployeeCertificateAction::class;
    }

    protected function deleteActionClass(): string
    {
        return DeleteEmployeeCertificateAction::class;
    }
}
