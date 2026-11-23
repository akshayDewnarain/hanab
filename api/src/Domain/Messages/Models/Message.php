<?php

namespace Domain\Messages\Models;

use Domain\Auth\Models\User;
use Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    public const TYPE_INTRODUCTION = 'introduction';

    public const TYPE_BIRTHDAY = 'birthday';

    public const TYPE_ANNIVERSARY = 'anniversary';

    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_CONTRACT = 'contract';

    public const CATEGORY_PEOPLE = 'people';

    public const CATEGORY_MILESTONES = 'milestones';

    public const CATEGORY_COMPANY = 'company';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** @var list<string> */
    public const REACTIONS = ['👍', '🎉', '👏'];

    protected $fillable = [
        'type',
        'category',
        'status',
        'title',
        'body',
        'subject_name',
        'detail',
        'years',
        'subject_employee_id',
        'author_id',
        'dedupe_key',
        'published_at',
        'occurs_on',
    ];

    protected $casts = [
        'years' => 'integer',
        'published_at' => 'datetime',
        'occurs_on' => 'date',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'subject_employee_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }
}
