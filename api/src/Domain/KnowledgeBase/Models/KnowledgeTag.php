<?php

namespace Domain\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeTag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'knowledge_article_tag');
    }
}
