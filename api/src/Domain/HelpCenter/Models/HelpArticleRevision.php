<?php

namespace Domain\HelpCenter\Models;

use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'help_article_id',
        'number',
        'title',
        'summary',
        'content',
        'editor_id',
        'note',
    ];

    protected $casts = [
        'help_article_id' => 'integer',
        'number' => 'integer',
        'editor_id' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'help_article_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
