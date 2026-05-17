<?php

namespace Domain\Auth\Models;

use Domain\Auth\Database\Factories\UserFactory;
use Domain\Auth\DataTransferObjects\UserData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\WithData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;

    /** @use WithData<UserData> */
    use WithData;

    /** {@inheritdoc} */
    protected $table = 'users';

    /** {@inheritdoc} */
    protected string $dataClass = UserData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /** {@inheritdoc} */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** {@inheritdoc} */
    protected $guarded = [
        'id',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_pictures')
            ->useDisk('public');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
