<?php

namespace Tests\Feature\Auth;

use Domain\Auth\Mail\UserInvitationMail;
use Domain\Auth\Models\Role;
use Domain\Auth\Models\User;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Auth\Support\UserInvitationService;
use Domain\Employees\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class InternalAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_accounts_are_provisioned_without_a_password_and_stay_hidden(): void
    {
        Mail::fake();

        $this->artisan('users:create-internal', [
            'email' => 'dev@hanab.test',
            '--name' => 'Developer',
            '--super-admin' => true,
            '--by' => 'Ada',
        ])->assertSuccessful()->doesntExpectOutputToContain('/invitations/');

        $developer = User::query()->where('email', 'dev@hanab.test')->firstOrFail();
        $this->assertTrue($developer->isInternalAccount());
        $this->assertTrue($developer->hasRole(BootstrapRoleNames::SUPER_ADMIN));
        $this->assertNull($developer->password);
        $this->assertNull($developer->password_set_at);
        $this->assertNull($developer->employee_id);
        $this->assertSame(0, Employee::query()->count());
        Mail::assertSent(UserInvitationMail::class);

        $activity = Activity::query()->where('event', 'internal_account_provisioned')->first();
        $this->assertNotNull($activity);
        $this->assertSame($developer->id, $activity->subject_id);
        $this->assertSame('Ada', $activity->properties->get('provisioned_by'));

        $this->artisan('users:create-internal', [
            'email' => 'dev@hanab.test',
            '--super-admin' => true,
        ])->assertFailed();
        $this->assertSame(1, User::query()->where('email', 'dev@hanab.test')->count());

        $admin = $this->actingAsSuperAdmin();
        $listed = $this->getJson('/api/v1/users?per_page=100')->assertOk();
        $this->assertNotContains('dev@hanab.test', array_column($listed->json('data'), 'email'));
        $this->assertSame(1, $listed->json('meta.total'));

        $this->getJson('/api/v1/users?search=dev@hanab.test')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/users?filter[email]=dev@hanab.test')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/users/'.$developer->id)->assertNotFound();
        $this->putJson('/api/v1/users/'.$developer->id, [
            'name' => 'Developer',
            'email' => 'dev@hanab.test',
            'status' => 'disabled',
        ])->assertNotFound();
        $this->deleteJson('/api/v1/users/'.$developer->id)->assertNotFound();
        $this->postJson('/api/v1/users/'.$developer->id.'/two-factor/reset')->assertNotFound();

        $roles = $this->getJson('/api/v1/roles')->assertOk()->json('data');
        $superAdmin = collect($roles)->firstWhere('name', BootstrapRoleNames::SUPER_ADMIN);
        $this->assertNotNull($superAdmin);
        $this->assertSame(1, $superAdmin['users_count']);

        $this->putJson('/api/v1/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_internal_account' => true,
        ])->assertStatus(422);
        $this->assertFalse($admin->fresh()->isInternalAccount());

        $admin->fill(['is_internal_account' => true]);
        $admin->save();
        $this->assertFalse($admin->fresh()->isInternalAccount());

        try {
            app(UserInvitationService::class)->invite(
                inviter: $admin,
                email: 'dev@hanab.test',
                name: 'Developer',
                roleId: $developer->roles()->first()?->id,
                allowSuperAdminRole: true,
            );
            $this->fail('A client invitation changed an internal account.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('This email cannot be invited.', $exception->getMessage());
        }

        $this->assertTrue($developer->fresh()->isInternalAccount());
    }

    public function test_clients_cannot_assign_or_change_the_reserved_role(): void
    {
        $role = Role::query()->where('name', BootstrapRoleNames::SUPER_ADMIN)->firstOrFail();
        $client = User::factory()->create();
        $client->givePermissionTo([
            PermissionService::viewPermission(Role::class),
            PermissionService::updatePermission(Role::class),
            PermissionService::deletePermission(Role::class),
        ]);
        $this->actingAs($client);

        $names = array_column($this->getJson('/api/v1/roles')->assertOk()->json('data'), 'name');
        $this->assertNotContains(BootstrapRoleNames::SUPER_ADMIN, $names);
        $this->getJson('/api/v1/roles/'.$role->id)->assertNotFound();
        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => 'owner',
            'permissions' => [],
        ])->assertNotFound();
        $this->deleteJson('/api/v1/roles/'.$role->id)->assertNotFound();

        $this->actingAsSuperAdmin();
        $this->putJson('/api/v1/roles/'.$role->id, [
            'name' => BootstrapRoleNames::SUPER_ADMIN,
            'permissions' => [],
        ])->assertStatus(422);
        $this->deleteJson('/api/v1/roles/'.$role->id)->assertStatus(422);
        $this->assertNotNull(Role::query()->where('name', BootstrapRoleNames::SUPER_ADMIN)->first());
    }

    public function test_an_internal_developer_uses_the_normal_login_and_can_be_disabled(): void
    {
        Mail::fake();
        $developer = User::factory()->withoutTwoFactor()->create([
            'email' => 'dev@hanab.test',
        ]);
        $developer->forceFill(['is_internal_account' => true])->save();
        $developer->assignRole(Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, 'web'));

        $login = $this->postJson('/api/login', [
            'email' => 'dev@hanab.test',
            'password' => 'password',
        ])->assertOk();
        $login->assertJsonPath('data.step', 'enroll');
        $this->assertArrayNotHasKey('token', $login->json('data'));
        $this->getJson('/api/user', [
            'Authorization' => 'Bearer '.$login->json('data.onboarding_token'),
        ])->assertForbidden()->assertJsonPath('code', 'mfa_required');

        $this->artisan('users:disable-internal', [
            'email' => 'dev@hanab.test',
            '--by' => 'Ada',
        ])->assertSuccessful();

        $this->assertSame('disabled', $developer->fresh()->status->value);
        $this->postJson('/api/login', [
            'email' => 'dev@hanab.test',
            'password' => 'password',
        ])->assertStatus(422);
        $this->assertNotNull(Activity::query()->where('event', 'internal_account_disabled')->first());

        $this->artisan('users:disable-internal', ['email' => 'owner@hanab.test'])->assertFailed();
    }
}
