<?php

namespace Domain\KnowledgeBase\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArticle extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_AUTHENTICATED = 'authenticated';

    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'category_id',
        'author_id',
        'editor_id',
        'title',
        'slug',
        'summary',
        'content',
        'search_text',
        'status',
        'visibility',
        'featured',
        'version',
        'published_at',
        'review_due_on',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'author_id' => 'integer',
        'editor_id' => 'integer',
        'featured' => 'boolean',
        'version' => 'integer',
        'published_at' => 'datetime',
        'review_due_on' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_article_tag');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(KnowledgeArticleRevision::class)->orderByDesc('number');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KnowledgeAttachment::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeArticleFeedback::class);
    }
}
