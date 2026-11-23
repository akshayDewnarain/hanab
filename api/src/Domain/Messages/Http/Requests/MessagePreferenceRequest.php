<?php

namespace Domain\Messages\Http\Requests;

use Domain\Support\Http\Requests\BaseRequest;

class MessagePreferenceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'people' => ['required', 'boolean'],
            'milestones' => ['required', 'boolean'],
            'company' => ['required', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
        ];
    }
}
