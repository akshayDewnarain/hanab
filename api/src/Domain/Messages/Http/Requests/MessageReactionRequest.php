<?php

namespace Domain\Messages\Http\Requests;

use Domain\Messages\Models\Message;
use Domain\Support\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MessageReactionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'emoji' => ['nullable', 'string', Rule::in(Message::REACTIONS)],
        ];
    }
}
