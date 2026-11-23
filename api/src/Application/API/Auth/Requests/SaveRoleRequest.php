<?php

namespace Application\API\Auth\Requests;

use Domain\Auth\Models\Role;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : null;
        $nameRules = [
            'required',
            'string',
            'max:255',
            Rule::unique(config('permission.table_names.roles'), 'name')->ignore($roleId),
        ];

        if (! ($role instanceof Role && $role->name === BootstrapRoleNames::SUPER_ADMIN)) {
            $nameRules[] = Rule::notIn([BootstrapRoleNames::SUPER_ADMIN]);
        }

        return [
            'name' => $nameRules,
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists(config('permission.table_names.permissions'), 'name')],
        ];
    }
}
