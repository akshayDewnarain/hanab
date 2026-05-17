<?php

namespace Domain\Employees\Http\Requests;

use Domain\Employees\Models\Skill;
use Domain\Support\Http\Requests\BaseModelRequest;
use Illuminate\Validation\Rule;

abstract class BaseSkillRequest extends BaseModelRequest
{
    public function rules(): array
    {
        $skill = $this->route('skill');
        $skill = $skill instanceof Skill ? $skill : null;

        $nameUnique = Rule::unique('skills', 'name');
        if ($skill !== null) {
            $nameUnique->ignore($skill->id);
        }

        $codeUnique = Rule::unique('skills', 'code');
        if ($skill !== null) {
            $codeUnique->ignore($skill->id);
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
                Rule::in(['technical', 'soft_skill', 'domain', 'safety']),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function model(): mixed
    {
        return Skill::class;
    }
}
