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
        Schema::create('external_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('token_hash', 64);
            $table->string('token_last_eight', 8);
            $table->string('min_level')->default('error');
            $table->unsignedInteger('time_window_minutes')->default(60);
            $table->unsignedInteger('count_threshold')->default(5);
            $table->json('group_by');
            $table->json('selector_tags')->nullable();
            $table->json('included_project_ids')->nullable();
            $table->json('excluded_project_ids')->nullable();
            $table->decimal('memory_percent_gte', 5, 2)->nullable();
            $table->decimal('disk_percent_gte', 5, 2)->nullable();
            $table->timestamp('token_generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_checks');
    }
};
