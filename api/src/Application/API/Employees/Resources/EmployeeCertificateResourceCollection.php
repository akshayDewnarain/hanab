<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCertificateResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = EmployeeCertificateResource::class;
}
