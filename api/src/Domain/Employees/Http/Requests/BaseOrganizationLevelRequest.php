<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\OrganizationLevel;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseOrganizationLevelRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $level = $this->route('organization_level');
        $level = $level instanceof OrganizationLevel ? $level : null;

        $nameUnique = Rule::unique('organization_levels', 'name');
        if ($level !== null) {
            $nameUnique->ignore($level->id);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameUnique,
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6})$/',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return OrganizationLevel::class;
    }
}
