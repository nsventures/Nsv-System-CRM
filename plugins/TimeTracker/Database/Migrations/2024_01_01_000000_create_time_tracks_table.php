<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('time_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->text('message')->nullable();
            $table->string('action')->nullable(); // For tracking different actions like 'start_work', 'break', 'idle', etc.
            $table->integer('employee_id')->nullable(); // To match the employeeId from Express.js
            $table->timestamps();

            // Add index for better performance
            $table->index(['user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_tracks');
    }
};
