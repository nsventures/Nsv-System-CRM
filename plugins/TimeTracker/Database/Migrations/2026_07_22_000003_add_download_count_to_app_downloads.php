<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The model and download route reference `download_count`, but the column was
 * never created — so hitting the /download route would error. Add it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_downloads', 'download_count')) {
            Schema::table('app_downloads', function (Blueprint $table) {
                $table->unsignedBigInteger('download_count')->default(0)->after('changelog');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_downloads', 'download_count')) {
            Schema::table('app_downloads', function (Blueprint $table) {
                $table->dropColumn('download_count');
            });
        }
    }
};
