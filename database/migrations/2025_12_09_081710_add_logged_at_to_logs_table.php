<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Add logged_at to preserve original log timestamp from the source application
            // This is separate from created_at which is when the log was received
            $table->timestamp('logged_at')->nullable()->after('referrer')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn('logged_at');
        });
    }
};
