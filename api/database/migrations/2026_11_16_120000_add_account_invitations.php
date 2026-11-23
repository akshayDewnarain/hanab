<?php

use Domain\Auth\Models\Role;
use Domain\Auth\Models\UserInvitation;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\BootstrapRoleNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->unique()->constrained('employees')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->timestamp('password_set_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('last_invitation_sent_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });

        DB::table('users')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->update([
                'status' => 'active',
                'password_set_at' => DB::raw('created_at'),
            ]);

        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('invited_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('status', 32);
            $table->string('token_hash', 64)->unique();
            $table->string('live_email')->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('resent_at')->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();
            $table->json('required_actions')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('expires_at');
        });

        Schema::create('installation_bootstraps', function (Blueprint $table) {
            $table->id();
            $table->string('purpose')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->timestamp('created_at')->nullable();
        });

        $guard = config('auth.defaults.guard');
        foreach (PermissionService::crudPermissions(UserInvitation::class) as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $role = Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, $guard);
        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->pluck('name')->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_bootstraps');
        Schema::dropIfExists('user_invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn(['status', 'password_set_at', 'invited_at', 'last_invitation_sent_at']);
        });
    }
};
