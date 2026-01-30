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
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 10)->default('view');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['user_id', 'permission']);
            $table->index(['project_id', 'permission']);

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
