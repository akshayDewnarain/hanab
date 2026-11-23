<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('employee_roles')
                ->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('employee_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('sort_order');
        });
    }
};
