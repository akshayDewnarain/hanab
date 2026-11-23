<?php

use Domain\Auth\Models\Role;
use Domain\Auth\Services\PermissionService;
use Domain\Auth\Support\BootstrapRoleNames;
use Domain\Employees\Models\OrganizationLevel;
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
        Schema::create('organization_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color', 7);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('organization_levels')->insert([
            ['name' => 'Executive', 'sort_order' => 10, 'color' => '#254F80', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Senior Management', 'sort_order' => 20, 'color' => '#386E9D', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Management', 'sort_order' => 30, 'color' => '#147F8D', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Team Lead', 'sort_order' => 40, 'color' => '#AD772E', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Employee', 'sort_order' => 50, 'color' => '#8B95A4', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('employee_roles', function (Blueprint $table) {
            $table->foreignId('organization_level_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('organization_levels')
                ->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('manager_id')
                ->nullable()
                ->after('employee_location_id')
                ->constrained('employees')
                ->nullOnDelete();
            $table->foreignId('organization_level_id')
                ->nullable()
                ->after('manager_id')
                ->constrained('organization_levels')
                ->nullOnDelete();
        });

        $guard = config('auth.defaults.guard');
        foreach (PermissionService::crudPermissions(OrganizationLevel::class) as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $role = Role::findOrCreate(BootstrapRoleNames::SUPER_ADMIN, $guard);
        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->pluck('name')->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_level_id');
            $table->dropConstrainedForeignId('manager_id');
        });

        Schema::table('employee_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_level_id');
        });

        Schema::dropIfExists('organization_levels');
    }
};
