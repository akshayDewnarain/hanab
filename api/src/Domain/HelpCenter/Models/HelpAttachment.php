<?php

namespace Domain\HelpCenter\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpAttachment extends Model
{
    protected $fillable = [
        'help_article_id',
        'uploaded_by',
        'filename',
        'path',
        'mime',
        'size',
        'caption',
        'alt_text',
    ];

    protected $casts = [
        'help_article_id' => 'integer',
        'uploaded_by' => 'integer',
        'size' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'help_article_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
