<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->index();
            $table->text('notes')->nullable();
            $table->timestamp('assessed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['employee_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
    }
};
