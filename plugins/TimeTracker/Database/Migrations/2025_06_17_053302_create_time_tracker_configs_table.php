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
        Schema::create('time_tracker_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Config key like 'screenshot_interval'
            $table->text('value'); // Config value (can be JSON for complex values)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_tracker_configs');
    }
};
