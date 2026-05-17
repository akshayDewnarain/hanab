<?php

namespace Application\API\Employees\Requests;

use App\Rules\RelationIds;

class UpdateEmployeeRequest extends BaseEmployeeRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048',
            ],
            'skill_ids' => [
                'sometimes',
                'nullable',
                new RelationIds('skills', 'id'),
            ],
            'certificate_ids' => [
                'sometimes',
                'nullable',
                new RelationIds('certificates', 'id'),
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($this->hasFile('image')) {
            $data['image'] = $this->file('image');
        }

        return $data;
    }
}
