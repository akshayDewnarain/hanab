<?php

namespace Application\API\Employees\Requests;

class CreateEmployeeRequest extends BaseEmployeeRequest
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
