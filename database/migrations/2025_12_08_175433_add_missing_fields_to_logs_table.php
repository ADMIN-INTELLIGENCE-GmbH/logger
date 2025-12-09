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
            $table->string('channel')->nullable()->after('level');
            $table->json('extra')->nullable()->after('context');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->text('request_url')->nullable()->after('method');
            $table->string('app_env')->nullable()->index();
            $table->boolean('app_debug')->nullable();
            $table->string('referrer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn([
                'channel',
                'extra',
                'user_agent',
                'request_url',
                'app_env',
                'app_debug',
                'referrer',
            ]);
        });
    }
};
