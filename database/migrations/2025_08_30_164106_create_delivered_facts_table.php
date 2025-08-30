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
        Schema::create('delivered_facts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignUuid('fact_id')->constrained('facts')->cascadeOnDelete();
            
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|delivered|seen|failed
            $table->string('channel')->default('push'); // push|in_app
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('meta')->nullable(); // Additional metadata
            
            // Ensure a fact is not delivered twice to the same user
            $table->unique(['user_id', 'fact_id']);
            
            $table->index(['user_id', 'scheduled_at']);
            $table->index(['package_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivered_facts');
    }
};
