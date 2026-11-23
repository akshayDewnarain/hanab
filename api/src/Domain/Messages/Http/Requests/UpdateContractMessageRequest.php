<?php

namespace Domain\Messages\Http\Requests;

use Domain\Messages\Http\Requests\Concerns\ValidatesMessageContent;
use Domain\Support\Http\Requests\BaseRequest;

class UpdateContractMessageRequest extends BaseRequest
{
    use ValidatesMessageContent;

    public function rules(): array
    {
        return $this->messageContentRules();
    }
}
