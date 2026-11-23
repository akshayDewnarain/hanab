<?php

namespace Tests\Feature\Auth;

use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_and_existing_users_reach_the_app_only_after_mfa(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $login->assertJsonPath('data.step', 'enroll');
        $this->assertArrayNotHasKey('token', $login->json('data'));
        $onboarding = $login->json('data.onboarding_token');

        $this->getJson('/api/user', $this->bearer($onboarding))->assertForbidden()
            ->assertJsonPath('code', 'mfa_required');

        $setup = $this->postJson('/api/v1/security/two-factor', [], $this->bearer($onboarding))->assertOk();
        $this->assertStringStartsWith('otpauth://totp/', (string) $setup->json('data.otpauth_uri'));

        $this->postJson('/api/v1/security/two-factor/confirm', ['code' => '000000'], $this->bearer($onboarding))
            ->assertStatus(422);

        $secret = (string) $user->fresh()->two_factor_secret;
        $code = app(Totp::class)->codeAt($secret, intdiv(time(), 30));
        $confirmed = $this->postJson('/api/v1/security/two-factor/confirm', ['code' => $code], $this->bearer($onboarding))
            ->assertOk();
        $this->getJson('/api/user', $this->bearer($onboarding))->assertForbidden();

        $this->postJson('/api/v1/security/two-factor/confirm', ['code' => $code], $this->bearer($onboarding))
            ->assertStatus(422);

        $recovery = $confirmed->json('data.recovery_codes');
        $this->assertCount(8, $recovery);

        $done = $this->postJson('/api/v1/security/two-factor/acknowledge', ['saved' => true], $this->bearer($onboarding))
            ->assertOk();
        $access = $done->json('data.token');
        $this->refreshAuth();
        $this->getJson('/api/user', $this->bearer($access))->assertOk();
        $this->assertTrue($user->fresh()->hasCompletedSecurity());

        $challenge = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();
        $challenge->assertJsonPath('data.step', 'challenge');
        $this->assertArrayNotHasKey('token', $challenge->json('data'));

        $next = app(Totp::class)->codeAt($secret, intdiv(time(), 30) + 1);
        $authed = $this->postJson('/api/v1/public/two-factor/challenge', [
            'challenge' => $challenge->json('data.challenge'),
            'code' => $next,
        ])->assertOk();
        $this->refreshAuth();
        $this->getJson('/api/user', $this->bearer($authed->json('data.token')))->assertOk();

        $replay = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();
        $this->postJson('/api/v1/public/two-factor/challenge', [
            'challenge' => $replay->json('data.challenge'),
            'code' => $next,
        ])->assertStatus(422);

        $withRecovery = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();
        $this->postJson('/api/v1/public/two-factor/challenge', [
            'challenge' => $withRecovery->json('data.challenge'),
            'code' => $recovery[0],
        ])->assertOk();
        $this->refreshAuth();

        $reused = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();
        $this->postJson('/api/v1/public/two-factor/challenge', [
            'challenge' => $reused->json('data.challenge'),
            'code' => $recovery[0],
        ])->assertStatus(422);
    }

    public function test_super_admin_and_legacy_tokens_cannot_skip_mfa(): void
    {
        $admin = User::factory()->withoutTwoFactor()->create();
        $admin->assignRole(Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, 'web'));
        $this->actingAs($admin);
        $this->getJson('/api/user')->assertForbidden()->assertJsonPath('code', 'mfa_required');
        $this->refreshAuth();

        $ready = User::factory()->create();
        $legacy = $ready->createToken('legacy', ['*'])->plainTextToken;
        $this->getJson('/api/user', $this->bearer($legacy))->assertForbidden();
        $this->refreshAuth();

        $current = $ready->createToken('auth_token', ['access-api'])->plainTextToken;
        $this->getJson('/api/user', $this->bearer($current))->assertOk();
    }

    public function test_password_reset_keeps_the_authenticator(): void
    {
        $user = User::factory()->create();
        $confirmed = $user->two_factor_confirmed_at;
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/public/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->two_factor_confirmed_at);
        $this->assertTrue($fresh->two_factor_confirmed_at->equalTo($confirmed));
        $this->assertTrue($fresh->hasCompletedSecurity());
    }

    public function test_failed_confirmations_are_rate_limited(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $token = $user->createToken('onboarding', ['onboarding'])->plainTextToken;
        $user->forceFill(['two_factor_secret' => app(Totp::class)->generateSecret()])->save();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/security/two-factor/confirm', ['code' => '111111'], $this->bearer($token))
                ->assertStatus(422);
        }

        $this->postJson('/api/v1/security/two-factor/confirm', ['code' => '111111'], $this->bearer($token))
            ->assertStatus(429);
    }

    private function refreshAuth(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * @return array<string, string>
     */
    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
