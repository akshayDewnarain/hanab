<?php

namespace Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'remember',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'remember' => 'boolean',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
