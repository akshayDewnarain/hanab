<?php

namespace Domain\Messages\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagePreference extends Model
{
    protected $fillable = [
        'user_id',
        'people',
        'milestones',
        'company',
        'push_notifications',
    ];

    protected $casts = [
        'people' => 'boolean',
        'milestones' => 'boolean',
        'company' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
