<?php

namespace Domain\Support\Transfer\Http\Requests;

use Domain\Support\Http\Requests\BaseRequest;
use Domain\Support\Transfer\TransferInspector;
use Illuminate\Validation\Rule;

abstract class TransferImportRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => [
                'required',
                'array',
                'min:1',
                'max:'.TransferInspector::MAX_ROWS,
            ],
            'rows.*' => [
                'array',
            ],
            'file_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'strategy' => [
                'nullable',
                'string',
                Rule::in(['create_only', 'upsert', 'update_only']),
            ],
            'policy' => [
                'nullable',
                'string',
                Rule::in(['strict', 'valid_rows']),
            ],
            'confirm_partial' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
