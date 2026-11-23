<?php

namespace Domain\HelpCenter\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticle extends Model
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
        'keywords',
        'status',
        'visibility',
        'context_key',
        'audience_permission',
        'feature_path',
        'app_version',
        'sort_order',
        'is_getting_started',
        'version',
        'published_at',
        'review_due_on',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'author_id' => 'integer',
        'editor_id' => 'integer',
        'sort_order' => 'integer',
        'is_getting_started' => 'boolean',
        'version' => 'integer',
        'published_at' => 'datetime',
        'review_due_on' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(HelpArticleRevision::class)->orderByDesc('number');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(HelpAttachment::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(HelpArticleFeedback::class);
    }
}
