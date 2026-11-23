<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_pending_secret')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_pending_secret');
            $table->unsignedBigInteger('two_factor_last_step')->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('onboarding_completed_at')->nullable()->after('two_factor_last_step');
        });

        Schema::create('user_recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at']);
        });

        Schema::create('auth_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        // Tokens issued before MFA cannot keep a wildcard ability that skips the new check.
        DB::table('personal_access_tokens')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_challenges');
        Schema::dropIfExists('user_recovery_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_pending_secret',
                'two_factor_confirmed_at',
                'two_factor_last_step',
                'onboarding_completed_at',
            ]);
        });
    }
};
