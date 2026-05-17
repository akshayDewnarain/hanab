<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\Certificate;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseCertificateRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $certificate = $this->route('certificate');
        $certificate = $certificate instanceof Certificate ? $certificate : null;

        $nameUnique = Rule::unique('certificates', 'name');
        if ($certificate !== null) {
            $nameUnique->ignore($certificate->id);
        }

        $codeUnique = Rule::unique('certificates', 'code');
        if ($certificate !== null) {
            $codeUnique->ignore($certificate->id);
        }

        $hexColor = ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameUnique,
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                $codeUnique,
            ],
            'label_background_color' => $hexColor,
            'label_text_color' => $hexColor,
            'label_border_color' => $hexColor,
            'category' => [
                'required',
                'string',
                'max:255',
                Rule::in(['safety', 'technical', 'compliance']),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'requires_expiry_date' => [
                'required',
                'boolean',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return Certificate::class;
    }
}
