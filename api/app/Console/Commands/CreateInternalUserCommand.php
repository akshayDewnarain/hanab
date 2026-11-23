<?php

namespace App\Console\Commands;

use Domain\Auth\Exceptions\InvitationDeliveryException;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\UserInvitationService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateInternalUserCommand extends Command
{
    protected $signature = 'users:create-internal
        {email : Your email, for the hidden setup account}
        {--name= : Name shown on the account}
        {--super-admin : Kept for compatibility; the setup account always receives super-admin}
        {--by= : Person provisioning the account, when known}
        {--resend : Send a fresh invitation when this account has not chosen a password yet}';

    protected $description = 'Invite your hidden setup account so you can prepare a client environment. The client does not see this account.';

    public function handle(UserInvitationService $invitations): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            if ($this->option('resend') && $existing->isInternalAccount() && ! $existing->hasPassword()) {
                return $this->resend($invitations, $existing);
            }

            $this->error('An account already uses that email.');

            return self::FAILURE;
        }

        if ($this->option('resend')) {
            $this->error('There is no internal account with that email to resend.');

            return self::FAILURE;
        }

        $roleId = $this->roleId();
        if ($roleId === false) {
            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));

        try {
            $invitation = $invitations->provisionInternal($email, $name, $roleId);
        } catch (InvitationDeliveryException) {
            $this->error('The account was created, but the invitation email could not be sent. Fix mail and run the command again with --resend.');

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $user = $invitation->invitedUser;
        $this->audit($user, 'internal_account_provisioned', [
            'provisioned_by' => $this->provisionedBy(),
            'super_admin' => $roleId !== null,
        ]);
        $this->info('An invitation was sent to '.$email.'.');
        $this->line('Set your password and authenticator, then prepare the environment.');
        $this->line('Invite the client with: php artisan hanab:bootstrap-admin client@example.com');

        return self::SUCCESS;
    }

    private function resend(UserInvitationService $invitations, User $user): int
    {
        try {
            $invitations->resendForUser($user);
        } catch (InvitationDeliveryException) {
            $this->error('The invitation email could not be sent.');

            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->audit($user, 'internal_account_invitation_resent', [
            'provisioned_by' => $this->provisionedBy(),
        ]);
        $this->info('A new invitation was sent to '.$user->email.'.');

        return self::SUCCESS;
    }

    private function roleId(): int|false|null
    {
        $role = Role::query()
            ->where('name', BootstrapRoleNames::SUPER_ADMIN)
            ->where('guard_name', config('auth.defaults.guard'))
            ->first();

        if ($role === null) {
            $this->error('The super-admin role is missing. Run migrations first.');

            return false;
        }

        return $role->id;
    }

    private function provisionedBy(): ?string
    {
        $named = trim((string) $this->option('by'));
        if ($named !== '') {
            return $named;
        }

        $system = getenv('USER');

        return is_string($system) && $system !== '' ? $system : null;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function audit(?User $user, string $event, array $properties): void
    {
        if ($user === null) {
            return;
        }

        try {
            activity('auth')
                ->performedOn($user)
                ->event($event)
                ->withProperties($properties)
                ->log($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
