<?php

namespace Domain\Employees\Http\Requests\QueryBuilders;

use Domain\Support\Http\Requests\BaseRequest;

/**
 * Colleague search is one term, not an employee index.
 * Matching runs through EmployeeQueryBuilder::matchingColleague().
 */
abstract class ExpertiseSearchQueryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
