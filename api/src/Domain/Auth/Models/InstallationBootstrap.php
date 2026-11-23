<?php

namespace Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationBootstrap extends Model
{
    public const PURPOSE_INITIAL_ADMIN = 'initial_admin';

    public $timestamps = false;

    protected $fillable = [
        'purpose',
        'user_id',
        'email',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
