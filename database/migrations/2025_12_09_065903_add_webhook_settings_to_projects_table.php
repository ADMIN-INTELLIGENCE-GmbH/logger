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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('webhook_enabled')->default(true)->after('webhook_url');
            $table->string('webhook_threshold')->default('error')->after('webhook_enabled');
            $table->string('webhook_secret', 64)->nullable()->after('webhook_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['webhook_enabled', 'webhook_threshold', 'webhook_secret']);
        });
    }
};
