<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('category')->index();
            $table->string('status')->index();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('subject_name')->nullable();
            $table->string('detail')->nullable();
            $table->unsignedSmallInteger('years')->nullable();
            $table->foreignId('subject_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('published_at')->nullable();
            $table->date('occurs_on')->nullable();
            $table->timestamps();
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });

        Schema::create('message_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('people')->default(true);
            $table->boolean('milestones')->default(true);
            $table->boolean('company')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_preferences');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('messages');
    }
};
