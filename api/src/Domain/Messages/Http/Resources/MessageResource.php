<?php

namespace Domain\Messages\Http\Resources;

use Domain\Auth\Models\User;
use Domain\Messages\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function __construct($resource, private readonly ?User $viewer = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Message $message */
        $message = $this->resource;
        $viewerId = $this->viewer?->id;
        $counts = [];

        foreach (Message::REACTIONS as $emoji) {
            $counts[] = [
                'emoji' => $emoji,
                'count' => $message->reactions->where('emoji', $emoji)->count(),
            ];
        }

        $own = $viewerId === null
            ? null
            : $message->reactions->firstWhere('user_id', $viewerId)?->emoji;

        return [
            'id' => $message->id,
            'type' => $message->type,
            'category' => $message->category,
            'status' => $message->status,
            'title' => $message->title,
            'body' => $message->body,
            'subject_name' => $message->subject_name,
            'detail' => $message->detail,
            'years' => $message->years,
            'author_name' => $message->author?->name,
            'published_at' => $message->published_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
            'updated_at' => $message->updated_at?->toIso8601String(),
            'reaction' => $own,
            'reactions' => $counts,
        ];
    }
}
