<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->nullable();
            $table->date('issued_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'certificate_id', 'certificate_number'], 'employee_certificate_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_certificates');
    }
};
