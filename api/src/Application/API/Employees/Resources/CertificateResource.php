<?php

namespace Application\API\Employees\Resources;

use Domain\Employees\Http\Resources\CertificateResource as DomainCertificateResource;
use Illuminate\Http\Request;

class CertificateResource extends DomainCertificateResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
        ];
    }
}
