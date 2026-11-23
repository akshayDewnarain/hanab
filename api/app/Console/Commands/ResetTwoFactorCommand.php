<?php

namespace App\Console\Commands;

use Domain\Auth\Models\User;
use Domain\Auth\Support\TwoFactorService;
use Illuminate\Console\Command;

class ResetTwoFactorCommand extends Command
{
    protected $signature = 'hanab:reset-two-factor {email : Account that lost both the authenticator and the recovery codes}';

    protected $description = 'Clear authenticator setup so the account enrolls again at the next sign-in. Use this when the last administrator is locked out.';

    public function handle(TwoFactorService $twoFactor): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No account uses that email.');

            return self::FAILURE;
        }

        $twoFactor->clearSecondFactor($user);
        $this->info('Authenticator setup was cleared. The next sign-in asks for the password and then a new authenticator.');

        return self::SUCCESS;
    }
}
