<?php

namespace Domain\Employees\Http\Resources;

use Domain\Employees\Models\Skill;
use Domain\Support\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/** @property-read Skill $resource */
abstract class SkillResource extends BaseResource
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
            'is_active',
        ]);
    }
}
