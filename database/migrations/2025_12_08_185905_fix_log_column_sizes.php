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
            // user_agent can be very long (some browsers send 500+ chars)
            $table->text('user_agent')->nullable()->change();

            // referrer URLs can be long
            $table->text('referrer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->change();
            $table->string('referrer')->nullable()->change();
        });
    }
};
