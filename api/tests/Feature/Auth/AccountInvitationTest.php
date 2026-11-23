<?php

namespace Tests\Feature\Auth;

use Domain\Auth\Database\Seeders\UserSeeder;
use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Mail\UserInvitationMail;
use Domain\Auth\Models\InstallationBootstrap;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Notifications\ResetPasswordNotification;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class AccountInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_database_has_no_login_and_production_seeding_stays_empty(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->app->detectEnvironment(fn () => 'production');
        (new UserSeeder)->run();
        $this->assertDatabaseCount('users', 0);
        $this->app->detectEnvironment(fn () => 'testing');
    }

    public function test_bootstrap_invites_one_super_admin_and_refuses_a_second_run(): void
    {
        Mail::fake();

        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test', '--name' => 'Owner'])
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@hanab.test')->firstOrFail();
        $this->assertSame(UserStatus::Invited, $user->status);
        $this->assertNull($user->password_set_at);
        $this->assertTrue($user->hasRole(BootstrapRoleNames::SUPER_ADMIN));
        $this->assertDatabaseCount('installation_bootstraps', 1);
        Mail::assertSent(UserInvitationMail::class, fn (UserInvitationMail $mail) => ! str_contains($mail->acceptUrl, 'password'));

        $this->artisan('hanab:bootstrap-admin', ['email' => 'other@hanab.test'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_a_failed_invitation_email_can_be_resent_without_a_second_account(): void
    {
        $this->app->instance(Mailer::class, new class extends Mailer
        {
            public function __construct() {}

            public function sendTo(string $email, \Illuminate\Mail\Mailable $mail): void
            {
                throw new RuntimeException('smtp down');
            }
        });

        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertNull(User::query()->first()->password_set_at);

        $this->app->forgetInstance(Mailer::class);
        Mail::fake();

        $this->artisan('hanab:bootstrap-admin', [
            'email' => 'owner@hanab.test',
            '--resend' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        Mail::assertSent(UserInvitationMail::class);
    }

    public function test_invitation_acceptance_is_single_use_and_ignores_a_role_in_the_body(): void
    {
        Mail::fake();
        config(['invitation.frontend_url' => 'https://hanab.test']);
        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test'])->assertSuccessful();
        $token = $this->invitationToken();

        $this->postJson('/api/v1/public/user-invitations/preview', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.state', 'valid')
            ->assertJsonPath('data.email', 'owner@hanab.test');

        $this->postJson('/api/v1/public/user-invitations/accept', [
            'token' => $token,
            'name' => 'Owner',
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
            'role_id' => 999,
            'user_id' => 999,
        ])->assertOk();

        $user = User::query()->where('email', 'owner@hanab.test')->firstOrFail();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('CorrectHorse1!', (string) $user->password));
        $this->assertFalse($user->hasCompletedSecurity());
        $this->assertTrue($user->hasRole(BootstrapRoleNames::SUPER_ADMIN));
        $this->assertSame(1, $user->roles()->count());

        $this->postJson('/api/v1/public/user-invitations/accept', [
            'token' => $token,
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
        ])->assertStatus(422);

        $this->postJson('/api/v1/public/user-invitations/preview', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.state', 'used');
    }

    public function test_expired_and_revoked_invitations_cannot_be_accepted(): void
    {
        Mail::fake();
        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test'])->assertSuccessful();
        $token = $this->invitationToken();

        $this->travel(8)->days();
        $this->postJson('/api/v1/public/user-invitations/preview', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.state', 'expired');
        $this->travelBack();

        Mail::fake();
        $this->artisan('hanab:bootstrap-admin', [
            'email' => 'owner@hanab.test',
            '--resend' => true,
        ])->assertSuccessful();
        $fresh = $this->invitationToken();
        $admin = User::query()->where('email', 'owner@hanab.test')->firstOrFail();
        $admin->forceFill([
            'password' => 'CorrectHorse1!',
            'password_set_at' => now(),
            'status' => UserStatus::Active,
            'two_factor_confirmed_at' => now(),
            'onboarding_completed_at' => now(),
        ])->save();

        $this->actingAs($admin);
        $this->deleteJson('/api/v1/users/'.$admin->id.'/invitation')->assertOk();
        $this->postJson('/api/v1/public/user-invitations/accept', [
            'token' => $fresh,
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
        ])->assertStatus(422);
    }

    public function test_an_activated_account_cannot_be_invited_again_and_a_colleague_can_be_linked_once(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $role = Role::query()->where('name', BootstrapRoleNames::SUPER_ADMIN)->firstOrFail();
        $job = EmployeeRole::factory()->create();
        $office = EmployeeLocation::factory()->create();
        $colleague = Employee::factory()->create([
            'employee_role_id' => $job->id,
            'employee_location_id' => $office->id,
        ]);

        Mail::fake();
        $this->postJson('/api/v1/users', [
            'email' => $admin->email,
            'role_id' => $role->id,
        ])->assertStatus(422);

        $this->postJson('/api/v1/users', [
            'email' => 'new.person@hanab.test',
            'name' => 'New Person',
            'role_id' => $role->id,
            'employee_id' => $colleague->id,
        ])->assertCreated();

        $invited = User::query()->where('email', 'new.person@hanab.test')->firstOrFail();
        $this->assertSame($colleague->id, $invited->employee_id);
        $this->assertTrue($invited->hasRole(BootstrapRoleNames::SUPER_ADMIN));

        $this->postJson('/api/v1/users', [
            'email' => 'second.person@hanab.test',
            'role_id' => $role->id,
            'employee_id' => $colleague->id,
        ])->assertStatus(422);
    }

    public function test_the_last_active_super_admin_cannot_be_removed_and_guests_cannot_manage_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();

        $admin = $this->actingAsSuperAdmin();

        $this->deleteJson('/api/v1/users/'.$admin->id)->assertStatus(422);
        $this->putJson('/api/v1/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => 'disabled',
        ])->assertStatus(422);

        $this->postJson('/api/v1/public/user-invitations/accept', [
            'token' => str_repeat('a', 40),
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
        ])->assertStatus(422);
    }

    public function test_password_reset_is_generic_and_does_not_replace_an_invitation(): void
    {
        Notification::fake();
        Mail::fake();
        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test'])->assertSuccessful();

        $this->postJson('/api/v1/public/forgot-password', ['email' => 'owner@hanab.test'])->assertOk();
        $this->postJson('/api/v1/public/forgot-password', ['email' => 'missing@hanab.test'])->assertOk();
        Notification::assertNothingSent();

        $admin = $this->actingAsSuperAdmin();
        $this->postJson('/api/v1/public/forgot-password', ['email' => $admin->email])->assertOk();
        Notification::assertSentTo($admin, ResetPasswordNotification::class);
    }

    public function test_work_location_writes_require_permission_and_sign_in_still_works(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $this->postJson('/api/v1/employee-locations', [
            'name' => 'Utrecht',
            'cluster_name' => 'Midden',
            'is_active' => true,
        ])->assertSuccessful();

        $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $admin->update(['status' => UserStatus::Disabled]);
        $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(422);

        $colleague = User::factory()->create();
        $this->actingAs($colleague);
        $this->postJson('/api/v1/employee-locations', [
            'name' => 'Amsterdam',
            'cluster_name' => 'West',
            'is_active' => true,
        ])->assertForbidden();

        $admin->update(['status' => UserStatus::Active]);
        $this->actingAs($admin);
        $this->postJson('/api/logout')->assertOk();
    }

    public function test_recovery_refuses_while_an_active_super_admin_remains(): void
    {
        Mail::fake();
        $this->artisan('hanab:bootstrap-admin', ['email' => 'owner@hanab.test'])->assertSuccessful();
        $token = $this->invitationToken();
        $this->postJson('/api/v1/public/user-invitations/accept', [
            'token' => $token,
            'password' => 'CorrectHorse1!',
            'password_confirmation' => 'CorrectHorse1!',
        ])->assertOk();

        $this->artisan('hanab:bootstrap-admin', [
            'email' => 'other@hanab.test',
            '--recover' => true,
        ])->assertFailed();
        $this->assertSame(1, InstallationBootstrap::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    private function invitationToken(): string
    {
        $token = null;
        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use (&$token) {
            $token = basename((string) parse_url($mail->acceptUrl, PHP_URL_PATH));

            return true;
        });

        return (string) $token;
    }
}
