<?php

namespace Domain\Support\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class MigrationHelper
{
    /**
     * Check if an index exists on a table.
     * Works with Doctrine DBAL if available; falls back to INFORMATION_SCHEMA for MySQL.
     */
    public static function indexExists(string $table, string $index): bool
    {
        // Try Doctrine first (if doctrine/dbal is installed)
        try {
            $conn = Schema::getConnection();
            $prefix = $conn->getTablePrefix();
            $sm = $conn->getDoctrineSchemaManager();

            // listTableIndexes expects the physical table name including prefix
            $indexes = $sm->listTableIndexes($prefix.$table);

            return array_key_exists($index, $indexes);
        } catch (Throwable $e) {
            // Fallback for MySQL/MariaDB via INFORMATION_SCHEMA
            try {
                $conn = Schema::getConnection();
                $dbName = $conn->getDatabaseName();
                $prefix = $conn->getTablePrefix();
                $fullTable = $prefix.$table;

                $row = DB::selectOne(
                    'SELECT COUNT(1) AS cnt
                     FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
                    [$dbName, $fullTable, $index]
                );

                return (bool) ($row?->cnt ?? 0);
            } catch (Throwable $ignored) {
                return false;
            }
        }
    }

    /**
     * Check if a foreign key constraint exists.
     */
    public static function foreignKeyExists(string $table, string $constraintName): bool
    {
        try {
            $conn = Schema::getConnection();
            $dbName = $conn->getDatabaseName();
            $prefix = $conn->getTablePrefix();
            $fullTable = $prefix.$table;

            $row = DB::selectOne(
                "SELECT COUNT(1) AS cnt
                 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = ?
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = ?",
                [$dbName, $fullTable, $constraintName]
            );

            return (bool) ($row?->cnt ?? 0);
        } catch (Throwable $e) {
            return false;
        }
    }
}
