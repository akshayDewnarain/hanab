<?php

namespace Domain\Messages\Actions;

use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;
use Illuminate\Validation\ValidationException;

class DeleteDraftMessageAction extends BaseAction
{
    public function execute(Message $message): void
    {
        if ($message->status !== Message::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'message' => 'Only a draft can be deleted.',
            ]);
        }

        $message->delete();
    }
}
