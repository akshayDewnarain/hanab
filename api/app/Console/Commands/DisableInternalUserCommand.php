<?php

namespace App\Console\Commands;

use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Models\AuthChallenge;
use Domain\Auth\Models\User;
use Domain\Auth\Support\UserInvitationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DisableInternalUserCommand extends Command
{
    protected $signature = 'users:disable-internal
        {email : Internal account to disable}
        {--by= : Person revoking the account, when known}';

    protected $description = 'Disable a hidden internal account and revoke its sessions. This command is not available over HTTP.';

    public function handle(UserInvitationService $invitations): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $user->isInternalAccount()) {
            $this->error('No internal account uses that email.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $invitations): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['status' => UserStatus::Disabled]);
            $locked->setRememberToken(Str::random(60));
            $locked->save();
            $locked->tokens()->delete();
            AuthChallenge::query()->where('user_id', $locked->id)->delete();
            DB::table('sessions')->where('user_id', $locked->id)->delete();

            foreach ($locked->invitations as $invitation) {
                if ($invitation->isPending()) {
                    $invitations->revoke($invitation);
                }
            }
        });

        $fresh = $user->fresh();
        if ($fresh instanceof User) {
            try {
            activity('auth')
                ->performedOn($fresh)
                ->event('internal_account_disabled')
                ->withProperties(['revoked_by' => $this->revokedBy()])
                ->log('internal_account_disabled');
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->info('The internal account was disabled and its sessions were revoked.');

        return self::SUCCESS;
    }

    private function revokedBy(): ?string
    {
        $named = trim((string) $this->option('by'));
        if ($named !== '') {
            return $named;
        }

        $system = getenv('USER');

        return is_string($system) && $system !== '' ? $system : null;
    }
}
