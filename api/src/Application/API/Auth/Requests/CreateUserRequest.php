<?php

namespace Application\API\Auth\Requests;

use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Validation\Rule;

class CreateUserRequest extends BaseUserRequest
{
    public function rules(): array
    {
        $authUser = $this->user();
        $roleExistsRule = Rule::exists(config('permission.table_names.roles'), 'id');
        if (! ($authUser instanceof User && $authUser->hasRole(BootstrapRoleNames::SUPER_ADMIN))) {
            $roleExistsRule = $roleExistsRule->where('name', '!=', BootstrapRoleNames::SUPER_ADMIN);
        }

        return [
            ...parent::rules(),
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'email' => [
                'required',
                'email',
                'string',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role_id' => [
                'required',
                'integer',
                $roleExistsRule,
            ],
        ];
    }
}
