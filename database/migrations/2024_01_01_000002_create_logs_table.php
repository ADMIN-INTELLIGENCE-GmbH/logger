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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_id');
            $table->string('level'); // error, info, debug, critical
            $table->text('message');
            $table->json('context')->nullable();
            
            // Indexed columns for filtering
            $table->string('controller')->nullable()->index();
            $table->string('route_name')->nullable()->index();
            $table->string('method')->nullable(); // GET, POST, etc
            $table->string('user_id')->nullable()->index(); // stored as string to support UUIDs or Integers
            $table->string('ip_address')->nullable()->index();
            
            $table->timestamp('created_at')->nullable()->index();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            // Composite index for common queries
            $table->index(['project_id', 'level']);
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
