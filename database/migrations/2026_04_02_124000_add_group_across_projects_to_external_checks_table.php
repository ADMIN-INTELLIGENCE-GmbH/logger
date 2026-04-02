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
        Schema::table('external_checks', function (Blueprint $table) {
            $table->boolean('group_across_projects')->default(false)->after('group_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_checks', function (Blueprint $table) {
            $table->dropColumn('group_across_projects');
        });
    }
};
