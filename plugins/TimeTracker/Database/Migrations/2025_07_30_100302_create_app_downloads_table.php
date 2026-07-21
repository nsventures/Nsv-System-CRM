<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // windows, mac, linux
            $table->string('arch')->nullable(); // intel, m1, x64, etc.
            $table->string('version');
            $table->string('file_path');
            $table->string('file_type'); // exe, dmg, tar.gz
            $table->text('changelog')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'arch', 'version'], 'unique_build_upload');
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_downloads');
    }
};
