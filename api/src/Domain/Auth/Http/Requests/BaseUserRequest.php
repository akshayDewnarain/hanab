<?php

namespace Domain\Auth\Http\Requests;

use Domain\Auth\Models\User;
use Domain\Support\Http\Requests\BaseModelRequest;

abstract class BaseUserRequest extends BaseModelRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function model(): mixed
    {
        return User::class;
    }
}
