<?php

namespace Domain\Auth\Models;

use Domain\Auth\Database\Factories\UserFactory;
use Domain\Auth\DataTransferObjects\UserData;
use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Notifications\ResetPasswordNotification;
use Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected string $guard_name = 'web';

    /** {@inheritdoc} */
    protected $table = 'users';

    /** {@inheritdoc} */
    protected string $dataClass = UserData::class;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'employee_id',
        'status',
        'password_set_at',
        'invited_at',
        'last_invitation_sent_at',
    ];

    /** {@inheritdoc} */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_pending_secret',
        'two_factor_last_step',
    ];

    /** {@inheritdoc} */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'employee_id' => 'integer',
            'status' => UserStatus::class,
            'password_set_at' => 'datetime',
            'invited_at' => 'datetime',
            'last_invitation_sent_at' => 'datetime',
            'is_internal_account' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_pending_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'invited_user_id');
    }

    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(UserRecoveryCode::class);
    }

    public function hasPassword(): bool
    {
        if ($this->password_set_at !== null) {
            return true;
        }

        $password = $this->getAuthPassword();

        return is_string($password) && $password !== '';
    }

    public function canSignIn(): bool
    {
        return $this->status === UserStatus::Active && $this->hasPassword();
    }

    public function canRequestPasswordReset(): bool
    {
        return $this->canSignIn();
    }

    public function hasCompletedSecurity(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->onboarding_completed_at !== null;
    }

    public function isInternalAccount(): bool
    {
        return (bool) $this->is_internal_account;
    }

    /**
     * Accounts that belong in client user management.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeClientVisible(Builder $query): Builder
    {
        return $query->where('is_internal_account', false);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->resolveRouteBindingQuery($this->newQuery()->clientVisible(), $value, $field)->first();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
            ->logExcept(['password', 'two_factor_secret', 'two_factor_pending_secret', 'two_factor_last_step'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
