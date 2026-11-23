<?php

namespace Tests\Feature\Auth;

use Domain\Auth\Models\User;
use Domain\Auth\Support\PersistentLogin;
use Domain\Auth\Support\Totp;
use Domain\Auth\Support\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class RememberLoginTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_remember_me_does_not_skip_the_authenticator(): void
    {
        $user = $this->enrolledUser();

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])->assertOk();

        $login->assertJsonPath('data.step', 'challenge');
        $this->assertArrayNotHasKey('token', $login->json('data'));
        $this->assertGuest();
        $this->assertNull($this->rememberCookie());
    }

    public function test_a_completed_sign_in_is_remembered_for_thirty_days(): void
    {
        $user = $this->enrolledUser();
        $signedIn = $this->finishChallenge($user, true);

        $signedIn->assertOk()->assertJsonPath('data.step', 'authenticated');
        $cookie = $this->rememberCookie();
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEqualsWithDelta(PersistentLogin::minutes(), ($cookie->getExpiresTime() - time()) / 60, 2);

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->withCredentials()
            ->withHeader('origin', 'http://localhost:3000')
            ->withCookie($cookie->getName(), $cookie->getValue())
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_signing_in_without_remember_does_not_keep_the_browser(): void
    {
        $user = $this->enrolledUser();
        $this->finishChallenge($user, false)->assertOk();

        $cookie = $this->rememberCookie();
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertLessThan(time(), $cookie->getExpiresTime());
    }

    public function test_resetting_the_authenticator_ends_a_remembered_sign_in(): void
    {
        $user = $this->enrolledUser();
        $this->finishChallenge($user, true)->assertOk();
        $cookie = $this->rememberCookie();
        $this->assertInstanceOf(Cookie::class, $cookie);

        app(TwoFactorService::class)->clearSecondFactor($user->fresh());
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $this->withCredentials()
            ->withHeader('origin', 'http://localhost:3000')
            ->withCookie($cookie->getName(), $cookie->getValue())
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_remember_me_survives_enrollment_and_still_requires_it(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])->assertOk();
        $login->assertJsonPath('data.step', 'enroll');
        $token = $login->json('data.onboarding_token');

        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token])
            ->assertForbidden()
            ->assertJsonPath('code', 'mfa_required');

        $this->postJson('/api/v1/security/two-factor', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $secret = (string) $user->fresh()->two_factor_secret;
        $code = app(Totp::class)->codeAt($secret, intdiv(time(), 30));
        $confirmed = $this->postJson('/api/v1/security/two-factor/confirm', [
            'code' => $code,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertCount(8, $confirmed->json('data.recovery_codes'));

        $this->postJson('/api/v1/security/two-factor/acknowledge', [
            'saved' => true,
        ], ['Authorization' => 'Bearer '.$token])->assertOk();

        $cookie = $this->rememberCookie();
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEqualsWithDelta(PersistentLogin::minutes(), ($cookie->getExpiresTime() - time()) / 60, 2);
    }

    private function enrolledUser(): User
    {
        $secret = app(Totp::class)->generateSecret();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret])->save();

        return $user;
    }

    private function finishChallenge(User $user, bool $remember): TestResponse
    {
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => $remember,
        ])->assertOk();

        $secret = (string) $user->two_factor_secret;
        $code = app(Totp::class)->codeAt($secret, intdiv(time(), 30));

        return $this->postJson('/api/v1/public/two-factor/challenge', [
            'challenge' => $login->json('data.challenge'),
            'code' => $code,
        ]);
    }

    private function rememberCookie(): ?Cookie
    {
        $name = Auth::guard('web')->getRecallerName();

        return cookie()->queued($name);
    }
}
