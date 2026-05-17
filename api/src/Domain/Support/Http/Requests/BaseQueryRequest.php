<?php

namespace Domain\Support\Http\Requests;

use Illuminate\Support\Collection;

abstract class BaseQueryRequest extends BaseRequest
{
    protected Collection $models;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ids' => [
                'nullable',
                'array',
            ],
            'ids.*' => [
                'required_with:ids',
            ],
            'data' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Retrieve the model's collection.
     */
    public function getModels(): Collection
    {
        return $this->models ?? collect();
    }

    /**
     * Set the models collection based on given IDs.
     */
    public function setModels(Collection $models): void
    {
        $this->models = $models;
    }
}
