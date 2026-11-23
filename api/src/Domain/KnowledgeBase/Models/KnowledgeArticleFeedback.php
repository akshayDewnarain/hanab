<?php

namespace Domain\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeArticleFeedback extends Model
{
    protected $table = 'knowledge_article_feedback';

    protected $fillable = [
        'knowledge_article_id',
        'user_id',
        'helpful',
    ];

    protected $casts = [
        'helpful' => 'boolean',
    ];
}
