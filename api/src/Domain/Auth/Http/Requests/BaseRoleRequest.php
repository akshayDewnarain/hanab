<?php

namespace Domain\Auth\Http\Requests;

use Domain\Auth\Models\Role;
use Domain\Support\Http\Requests\BaseModelRequest;

abstract class BaseRoleRequest extends BaseModelRequest
{
    public function rules(): array
    {
        return [
            'permissions' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }

    public function model(): mixed
    {
        return Role::class;
    }
}
