<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('cluster_name')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['name', 'cluster_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_locations');
    }
};
