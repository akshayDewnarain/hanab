<?php

namespace Domain\Auth\Support;

use Domain\Auth\Models\AuthChallenge;
use Domain\Auth\Models\User;
use Domain\Auth\Models\UserRecoveryCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TwoFactorService
{
    public function __construct(private readonly Totp $totp) {}

    /**
     * @return array{otpauth_uri: string, manual_key: string}
     */
    public function beginEnrollment(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasCompletedSecurity() && $locked->two_factor_pending_secret === null) {
                throw new TwoFactorException('An authenticator is already configured.', 409);
            }

            $secret = $locked->two_factor_secret;
            if (! is_string($secret) || $secret === '') {
                $secret = $this->totp->generateSecret();
                $locked->forceFill(['two_factor_secret' => $secret])->save();
            }

            return $this->present($secret, $locked->email);
        });
    }

    /**
     * @return array{otpauth_uri: string, manual_key: string}
     */
    public function beginReplacement(User $user, string $password, string $code, ?string $ip): array
    {
        $this->assertPassword($user, $password);
        $this->guardAttempts($user, $ip);

        return DB::transaction(function () use ($user, $code, $ip): array {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $locked->hasCompletedSecurity()) {
                throw new TwoFactorException('Set up an authenticator before replacing it.');
            }

            if (! $this->acceptCurrentFactor($locked, $code, true)) {
                $this->failAttempt($locked, $ip);
            }

            $secret = $this->totp->generateSecret();
            $locked->forceFill(['two_factor_pending_secret' => $secret])->save();
            $this->clearAttempts($locked, $ip);

            return $this->present($secret, $locked->email);
        });
    }

    /**
     * @return list<string>
     */
    public function confirmEnrollment(User $user, string $code, ?string $ip): array
    {
        $this->guardAttempts($user, $ip);

        return DB::transaction(function () use ($user, $code, $ip): array {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $pending = is_string($locked->two_factor_pending_secret) && $locked->two_factor_pending_secret !== '';
            if ($locked->hasCompletedSecurity() && ! $pending) {
                throw new TwoFactorException('Start authenticator replacement before confirming a new code.');
            }
            $secret = $pending ? $locked->two_factor_pending_secret : $locked->two_factor_secret;

            if (! is_string($secret) || $secret === '') {
                throw new TwoFactorException('Start authenticator setup before confirming a code.');
            }

            $step = $this->totp->verify($secret, $code);
            if ($step === null || ($locked->two_factor_last_step !== null && $step <= (int) $locked->two_factor_last_step && ! $pending)) {
                $this->failAttempt($locked, $ip);
            }

            $locked->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_pending_secret' => null,
                'two_factor_confirmed_at' => $locked->two_factor_confirmed_at ?? now(),
                'two_factor_last_step' => $step,
            ])->save();

            $codes = $this->replaceRecoveryCodes($locked);
            $this->clearAttempts($locked, $ip);
            $this->record($locked, $pending ? 'authenticator_replaced' : 'authenticator_confirmed');

            return $codes;
        });
    }

    public function acknowledge(User $user): void
    {
        if ($user->two_factor_confirmed_at === null || ! $user->recoveryCodes()->whereNull('used_at')->exists()) {
            throw new TwoFactorException('Confirm an authenticator and keep the recovery codes before continuing.');
        }

        if ($user->onboarding_completed_at === null) {
            $user->forceFill(['onboarding_completed_at' => now()])->save();
            $this->record($user, 'onboarding_completed');
        }
    }

    public function issueChallenge(User $user, bool $remember = false): string
    {
        AuthChallenge::query()->where('expires_at', '<', now())->delete();
        $plain = Str::random(64);
        AuthChallenge::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'remember' => $remember,
            'expires_at' => now()->addMinutes(5),
        ]);

        return $plain;
    }

    /**
     * @return array{user: User, remember: bool}
     */
    public function consumeChallenge(string $plain, string $code, ?string $ip): array
    {
        return DB::transaction(function () use ($plain, $code, $ip): array {
            $challenge = AuthChallenge::query()
                ->where('token_hash', hash('sha256', $plain))
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->consumed_at !== null || $challenge->expires_at->isPast()) {
                throw new TwoFactorException('This sign-in challenge has expired. Sign in again.');
            }

            $user = User::query()->whereKey($challenge->user_id)->lockForUpdate()->first();
            if ($user === null || ! $user->canSignIn() || $user->two_factor_confirmed_at === null) {
                throw new TwoFactorException('This sign-in challenge has expired. Sign in again.');
            }

            $this->guardAttempts($user, $ip);
            if (! $this->acceptCurrentFactor($user, $code, true)) {
                $this->failAttempt($user, $ip);
            }

            $challenge->forceFill(['consumed_at' => now()])->save();
            $this->clearAttempts($user, $ip);
            $this->record($user, 'two_factor_challenge_passed');

            return [
                'user' => $user,
                'remember' => (bool) $challenge->remember,
            ];
        });
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user, string $password, string $code, ?string $ip): array
    {
        $this->assertPassword($user, $password);
        $this->guardAttempts($user, $ip);

        return DB::transaction(function () use ($user, $code, $ip): array {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $locked->hasCompletedSecurity()) {
                throw new TwoFactorException('Finish authenticator setup before managing recovery codes.');
            }
            if (! $this->acceptCurrentFactor($locked, $code, true)) {
                $this->failAttempt($locked, $ip);
            }

            $codes = $this->replaceRecoveryCodes($locked);
            $this->clearAttempts($locked, $ip);
            $this->record($locked, 'recovery_codes_regenerated');

            return $codes;
        });
    }

    public function clearSecondFactor(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill([
                'two_factor_secret' => null,
                'two_factor_pending_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_step' => null,
                'onboarding_completed_at' => null,
            ]);
            $locked->setRememberToken(Str::random(60));
            $locked->save();
            $locked->recoveryCodes()->delete();
            $locked->tokens()->delete();
            AuthChallenge::query()->where('user_id', $locked->id)->delete();
            DB::table('sessions')->where('user_id', $locked->id)->delete();
            $this->record($locked, 'two_factor_reset');
        });
    }

    public function issueOnboardingToken(User $user, bool $remember = false): string
    {
        $user->tokens()->where('name', 'onboarding')->delete();
        $abilities = ['onboarding'];
        if ($remember) {
            $abilities[] = 'remember';
        }

        return $user->createToken('onboarding', $abilities, now()->addMinutes(30))->plainTextToken;
    }

    public function issueAccessToken(User $user): string
    {
        return $user->createToken('auth_token', ['access-api'])->plainTextToken;
    }

    /**
     * @return array{otpauth_uri: string, manual_key: string}
     */
    private function present(string $secret, string $email): array
    {
        return [
            'otpauth_uri' => $this->totp->provisioningUri($secret, $email),
            'manual_key' => trim(chunk_split($secret, 4, ' ')),
        ];
    }

    /**
     * @return list<string>
     */
    private function replaceRecoveryCodes(User $user): array
    {
        $user->recoveryCodes()->delete();
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(6)));
            $codes[] = substr($raw, 0, 4).'-'.substr($raw, 4, 4).'-'.substr($raw, 8, 4);
        }

        foreach ($codes as $code) {
            UserRecoveryCode::query()->create([
                'user_id' => $user->id,
                'code_hash' => $this->hashCode($code),
            ]);
        }

        return $codes;
    }

    private function acceptCurrentFactor(User $user, string $code, bool $consumeRecovery): bool
    {
        $digits = preg_replace('/\s+/', '', $code) ?? '';
        if (preg_match('/^\d{6}$/', $digits)) {
            $secret = $user->two_factor_secret;
            if (! is_string($secret) || $secret === '') {
                return false;
            }
            $step = $this->totp->verify($secret, $digits);
            if ($step === null || ($user->two_factor_last_step !== null && $step <= (int) $user->two_factor_last_step)) {
                return false;
            }
            $user->forceFill(['two_factor_last_step' => $step])->save();

            return true;
        }

        if (! $consumeRecovery) {
            return false;
        }

        $hash = $this->hashCode($code);
        $match = $user->recoveryCodes()->whereNull('used_at')->lockForUpdate()->get()
            ->first(fn (UserRecoveryCode $row) => hash_equals($row->code_hash, $hash));

        if ($match === null) {
            return false;
        }

        $match->forceFill(['used_at' => now()])->save();

        return true;
    }

    private function hashCode(string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code) ?? '');

        return hash('sha256', $normalized);
    }

    private function assertPassword(User $user, string $password): void
    {
        if (! password_verify($password, (string) $user->password)) {
            throw new TwoFactorException('The current password is incorrect.');
        }
    }

    private function guardAttempts(User $user, ?string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->attemptKey($user, $ip), 5)) {
            throw new TwoFactorException('Too many attempts. Wait a minute and try again.', 429);
        }
    }

    private function failAttempt(User $user, ?string $ip): never
    {
        RateLimiter::hit($this->attemptKey($user, $ip), 60);

        try {
            activity('security')
                ->causedBy($user)
                ->performedOn($user)
                ->event('two_factor_failed')
                ->withProperties(['event' => 'two_factor_failed'])
                ->log('two_factor_failed');
        } catch (\Throwable $exception) {
            report($exception);
        }

        throw new TwoFactorException('The verification code is invalid or expired.');
    }

    private function clearAttempts(User $user, ?string $ip): void
    {
        RateLimiter::clear($this->attemptKey($user, $ip));
    }

    private function attemptKey(User $user, ?string $ip): string
    {
        return 'mfa:'.$user->id.'|'.($ip ?? 'unknown');
    }

    private function record(User $user, string $event): void
    {
        activity('security')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['event' => $event])
            ->log($event);
    }
}
