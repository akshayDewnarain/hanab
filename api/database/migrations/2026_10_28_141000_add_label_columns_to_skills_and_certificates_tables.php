<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The create migrations already define these columns. Databases migrated
     * before that change still need them added in place.
     */
    public function up(): void
    {
        $this->addLabelColumns('skills');
        $this->addLabelColumns('certificates');
    }

    public function down(): void
    {
        $this->dropLabelColumns('skills');
        $this->dropLabelColumns('certificates');
    }

    private function addLabelColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'code')) {
                $blueprint->string('code', 32)->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn($table, 'label_background_color')) {
                $blueprint->string('label_background_color', 7)->nullable()->after('code');
            }

            if (! Schema::hasColumn($table, 'label_text_color')) {
                $blueprint->string('label_text_color', 7)->nullable()->after('label_background_color');
            }

            if (! Schema::hasColumn($table, 'label_border_color')) {
                $blueprint->string('label_border_color', 7)->nullable()->after('label_text_color');
            }
        });
    }

    private function dropLabelColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (Schema::hasColumn($table, 'code')) {
                $blueprint->dropUnique(['code']);
            }

            $columns = array_values(array_filter(
                ['code', 'label_background_color', 'label_text_color', 'label_border_color'],
                fn (string $column): bool => Schema::hasColumn($table, $column),
            ));

            if ($columns !== []) {
                $blueprint->dropColumn($columns);
            }
        });
    }
};
