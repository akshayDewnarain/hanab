<?php

namespace Domain\Messages\Actions;

use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;
use Illuminate\Validation\ValidationException;

class UpdateContractMessageAction extends BaseAction
{
    public function execute(Message $message, string $title, string $body): Message
    {
        if ($message->type !== Message::TYPE_CONTRACT) {
            throw ValidationException::withMessages([
                'message' => 'Only a company message can be edited.',
            ]);
        }

        $message->title = $title;
        $message->body = $body;
        $message->save();

        return $message->load(['reactions', 'author']);
    }
}
