<?php

namespace Domain\KnowledgeBase\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAttachment extends Model
{
    protected $fillable = [
        'knowledge_article_id',
        'uploaded_by',
        'filename',
        'path',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
