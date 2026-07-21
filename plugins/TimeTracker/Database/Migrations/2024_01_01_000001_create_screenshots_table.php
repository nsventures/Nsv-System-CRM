<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('screenshot_path');
            $table->timestamp('captured_at');
            $table->string('filename')->nullable(); // Store original filename
            $table->integer('file_size')->nullable(); // Store file size in bytes
            $table->json('metadata')->nullable(); // Store additional metadata if needed
            $table->timestamps();

            // Add index for better performance
            $table->index(['user_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
