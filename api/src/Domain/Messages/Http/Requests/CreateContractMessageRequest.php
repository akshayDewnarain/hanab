<?php

namespace Domain\Messages\Http\Requests;

use Domain\Messages\Http\Requests\Concerns\ValidatesMessageContent;
use Domain\Messages\Models\Message;
use Domain\Support\Http\Requests\BaseRequest;

class CreateContractMessageRequest extends BaseRequest
{
    use ValidatesMessageContent;

    public function rules(): array
    {
        return [
            ...$this->messageContentRules(),
            'status' => ['sometimes', 'in:'.Message::STATUS_DRAFT.','.Message::STATUS_PUBLISHED],
        ];
    }
}
