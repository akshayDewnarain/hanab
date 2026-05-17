<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

abstract class BaseEmployeeLocationRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $location = $this->route('employee_location');
        $location = $location instanceof EmployeeLocation ? $location : null;

        $nameUnique = Rule::unique('employee_locations', 'name')
            ->where(fn (Builder $query): Builder => $query->where(
                'cluster_name',
                $this->input('cluster_name')
            ));
        if ($location !== null) {
            $nameUnique->ignore($location->id);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameUnique,
            ],
            'cluster_name' => [
                'required',
                'string',
                'max:255',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return EmployeeLocation::class;
    }
}
