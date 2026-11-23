<?php

namespace App\Console\Commands;

use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Exceptions\InvitationDeliveryException;
use Domain\Auth\Models\InstallationBootstrap;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\SuperAdminGuard;
use Domain\Auth\Support\UserInvitationService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;

class BootstrapAdminCommand extends Command
{
    protected $signature = 'hanab:bootstrap-admin
        {email : Email address for the initial administrator}
        {--name= : Name to store on the account}
        {--resend : Send a fresh activation link for the unactivated initial administrator}
        {--recover : Create a new activation link when no active super-admin remains}';

    protected $description = 'Invite the first Hanab administrator. This command is not available over HTTP.';

    public function handle(UserInvitationService $invitations): int
    {
        if (! Schema::hasTable('user_invitations') || ! Schema::hasColumn('users', 'status')) {
            $this->error('Run php artisan migrate before bootstrapping the administrator.');

            return self::FAILURE;
        }

        $guard = config('auth.defaults.guard');
        $roleReady = Role::query()->where('name', BootstrapRoleNames::SUPER_ADMIN)->where('guard_name', $guard)->exists();
        $permissionsReady = Permission::query()->where('name', 'create users')->where('guard_name', $guard)->exists();

        if (! $roleReady || ! $permissionsReady) {
            $this->error('The super-admin role or its permissions are missing. Run migrations first.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        try {
            return Cache::lock('hanab-bootstrap-admin', 30)->block(5, function () use ($invitations, $email): int {
                $bootstrap = InstallationBootstrap::query()
                    ->where('purpose', InstallationBootstrap::PURPOSE_INITIAL_ADMIN)
                    ->first();

                if ($this->option('recover')) {
                    return $this->recover($invitations, $bootstrap, $email);
                }

                if ($this->option('resend')) {
                    return $this->resend($invitations, $bootstrap, $email);
                }

                if ($bootstrap !== null) {
                    $this->error('This installation already has an initial administrator.');
                    $this->line('To send the activation link again before it is used, run the command with --resend.');
                    $this->line('When no active super-admin remains, run it with --recover.');

                    return self::FAILURE;
                }

                return $this->create($invitations, $email);
            });
        } catch (LockTimeoutException) {
            $this->error('Another bootstrap is already running.');

            return self::FAILURE;
        }
    }

    private function create(UserInvitationService $invitations, string $email): int
    {
        try {
            DB::transaction(function () use ($email): void {
                InstallationBootstrap::query()->create([
                    'purpose' => InstallationBootstrap::PURPOSE_INITIAL_ADMIN,
                    'email' => $email,
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable) {
            $this->error('This installation already has an initial administrator.');

            return self::FAILURE;
        }

        return $this->sendInitialInvitation($invitations, $email, 'initial_admin_bootstrapped');
    }

    private function resend(UserInvitationService $invitations, ?InstallationBootstrap $bootstrap, string $email): int
    {
        if ($bootstrap === null || mb_strtolower($bootstrap->email) !== $email) {
            $this->error('There is no unactivated bootstrap invitation for that email.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('The bootstrap account no longer exists. Run the command with --recover.');

            return self::FAILURE;
        }

        if ($user->hasPassword()) {
            $this->error('The initial administrator has already chosen a password.');

            return self::FAILURE;
        }

        try {
            $invitation = $invitations->resendForUser($user);
        } catch (InvitationDeliveryException) {
            $this->deliveryFailed($email);

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->audit('initial_admin_invitation_resent', $user, $invitation->id);
        $this->info('A new activation email was sent to '.$email.'.');

        return self::SUCCESS;
    }

    private function recover(UserInvitationService $invitations, ?InstallationBootstrap $bootstrap, string $email): int
    {
        if (SuperAdminGuard::activeExists()) {
            $this->error('An active super-admin already exists. Recovery is only for an installation with none left.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();
        $superAdminRemains = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', BootstrapRoleNames::SUPER_ADMIN))
            ->exists();

        if ($existing !== null && ! $existing->hasRole(BootstrapRoleNames::SUPER_ADMIN) && $superAdminRemains) {
            $this->error('That email belongs to an account that is not the super-admin.');

            return self::FAILURE;
        }

        if ($existing?->hasPassword()) {
            $existing->tokens()->delete();
            $existing->update([
                'password' => null,
                'password_set_at' => null,
                'status' => UserStatus::Invited,
            ]);
        }

        if ($bootstrap === null) {
            InstallationBootstrap::query()->create([
                'purpose' => InstallationBootstrap::PURPOSE_INITIAL_ADMIN,
                'user_id' => $existing?->id,
                'email' => $email,
                'created_at' => now(),
            ]);
        } else {
            $bootstrap->update([
                'email' => $email,
                'user_id' => $existing?->id,
            ]);
        }

        return $this->sendInitialInvitation($invitations, $email, 'initial_admin_recovered', $existing);
    }

    private function sendInitialInvitation(
        UserInvitationService $invitations,
        string $email,
        string $event,
        ?User $existing = null,
    ): int {
        $role = Role::query()
            ->where('name', BootstrapRoleNames::SUPER_ADMIN)
            ->where('guard_name', config('auth.defaults.guard'))
            ->firstOrFail();

        try {
            $invitation = $existing !== null && $existing->fresh()?->hasRole(BootstrapRoleNames::SUPER_ADMIN)
                ? $invitations->resendForUser($existing->fresh())
                : $invitations->invite(
                    inviter: null,
                    email: $email,
                    name: $this->option('name') ?: $existing?->name,
                    roleId: $role->id,
                    allowSuperAdminRole: true,
                );
        } catch (InvitationDeliveryException) {
            $this->deliveryFailed($email);

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            if ($event === 'initial_admin_bootstrapped') {
                InstallationBootstrap::query()->where('purpose', InstallationBootstrap::PURPOSE_INITIAL_ADMIN)->delete();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        InstallationBootstrap::query()
            ->where('purpose', InstallationBootstrap::PURPOSE_INITIAL_ADMIN)
            ->update([
                'user_id' => $invitation->invited_user_id,
                'email' => $email,
            ]);

        $this->audit($event, $invitation->invitedUser, $invitation->id);
        $this->info('Activation email sent to '.$email.'.');

        return self::SUCCESS;
    }

    private function deliveryFailed(string $email): void
    {
        $this->warn('The administrator account is ready, but the activation email could not be sent.');
        $this->line('Fix mail delivery, then run: php artisan hanab:bootstrap-admin '.$email.' --resend');
    }

    private function audit(string $event, ?User $user, int $invitationId): void
    {
        if ($user === null) {
            return;
        }

        try {
            activity('auth')
                ->performedOn($user)
                ->event($event)
                ->withProperties([
                    'email' => $user->email,
                    'invitation_id' => $invitationId,
                ])
                ->log('Initial administrator invitation updated');
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
