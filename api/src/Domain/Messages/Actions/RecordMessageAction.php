<?php

namespace Domain\Messages\Actions;

use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;

class RecordMessageAction extends BaseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Message
    {
        $key = $attributes['dedupe_key'] ?? null;
        if (is_string($key) && $key !== '') {
            $existing = Message::query()->where('dedupe_key', $key)->first();
            if ($existing instanceof Message) {
                return $existing;
            }
        }

        return Message::query()->create($attributes);
    }
}
