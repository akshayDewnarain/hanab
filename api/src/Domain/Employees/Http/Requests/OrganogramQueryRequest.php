<?php

namespace Domain\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganogramQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'view' => ['nullable', 'string', Rule::in(['management', 'roles'])],
            'location_id' => ['nullable', 'integer', Rule::exists('employee_locations', 'id')],
            'organization_level_id' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function viewName(): string
    {
        $view = $this->validated('view');

        return is_string($view) && $view !== '' ? $view : 'management';
    }

    public function locationId(): ?int
    {
        $value = $this->validated('location_id');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function levelFilter(): ?string
    {
        $value = $this->validated('organization_level_id');
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
