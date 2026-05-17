<?php

namespace Application\API\Employees\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CertificateResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = CertificateResource::class;
}
