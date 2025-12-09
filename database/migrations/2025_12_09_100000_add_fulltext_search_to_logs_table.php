<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB: Add FULLTEXT index on message column
            // First, we need to make sure the message column is TEXT type (not MEDIUMTEXT or LONGTEXT for FULLTEXT)
            DB::statement('ALTER TABLE logs ADD FULLTEXT INDEX logs_message_fulltext (message)');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Add GIN index for full-text search
            // Create a generated column for the tsvector and add GIN index
            DB::statement("ALTER TABLE logs ADD COLUMN IF NOT EXISTS message_search tsvector GENERATED ALWAYS AS (to_tsvector('english', COALESCE(message, ''))) STORED");
            DB::statement('CREATE INDEX IF NOT EXISTS logs_message_search_idx ON logs USING GIN (message_search)');
        }
        // SQLite doesn't support full-text search indexes in the same way,
        // but we can use FTS5 virtual tables if needed. For now, we'll skip SQLite
        // and fall back to LIKE queries which is acceptable for development.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE logs DROP INDEX logs_message_fulltext');
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS logs_message_search_idx');
            DB::statement('ALTER TABLE logs DROP COLUMN IF EXISTS message_search');
        }
    }
};
