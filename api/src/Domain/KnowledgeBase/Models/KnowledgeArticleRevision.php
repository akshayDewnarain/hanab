<?php

namespace Domain\KnowledgeBase\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeArticleRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'knowledge_article_id',
        'number',
        'title',
        'summary',
        'content',
        'editor_id',
        'note',
    ];

    protected $casts = [
        'number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
