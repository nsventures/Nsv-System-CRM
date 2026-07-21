<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_tracker_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // Action type: 'start_work', 'end_work', 'break', 'idle', etc.
            $table->timestamp('timestamp'); // When the action occurred
            $table->json('metadata')->nullable(); // Store additional data as JSON
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['user_id', 'timestamp']);
            $table->index(['action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_tracker_activity_logs');
    }
};
