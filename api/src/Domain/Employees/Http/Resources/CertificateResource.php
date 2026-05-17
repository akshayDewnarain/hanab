<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\Certificate;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read Certificate $resource */
abstract class CertificateResource extends BaseResource
{
    public function toArray(Request $request)
    {
        return $this->withAttributes([
            'name',
            'code',
            'label_background_color',
            'label_text_color',
            'label_border_color',
            'category',
            'description',
            'requires_expiry_date',
            'is_active',
        ]);
    }
}
