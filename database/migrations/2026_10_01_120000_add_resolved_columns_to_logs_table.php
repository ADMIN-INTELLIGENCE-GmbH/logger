<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nullable columns only, no index or foreign key, so MySQL 8 can add them
     * without rebuilding the (large) logs table.
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->string('resolution_note', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn(['resolved_at', 'resolved_by', 'resolution_note']);
        });
    }
};
