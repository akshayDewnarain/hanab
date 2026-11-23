<?php

namespace Domain\HelpCenter\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticleFeedback extends Model
{
    protected $fillable = [
        'help_article_id',
        'user_id',
        'helpful',
    ];

    protected $casts = [
        'help_article_id' => 'integer',
        'user_id' => 'integer',
        'helpful' => 'boolean',
    ];
}
