<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Relax the app_downloads table so the Downloads section can host ANY setup/file,
 * not just OS installers: platform / version / file_type become optional and a
 * `title` is added for a human-friendly label. The existing
 * unique(platform, arch, version) index is kept — NULLs are treated as distinct
 * in MySQL, so general files (all-null) never collide, while real installer builds
 * stay de-duplicated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('app_downloads', 'title')) {
            Schema::table('app_downloads', function (Blueprint $table) {
                $table->string('title')->nullable()->after('id');
            });
        }

        // Raw ALTERs so we don't depend on doctrine/dbal for ->change().
        DB::statement('ALTER TABLE app_downloads MODIFY platform VARCHAR(255) NULL');
        DB::statement('ALTER TABLE app_downloads MODIFY version VARCHAR(255) NULL');
        DB::statement('ALTER TABLE app_downloads MODIFY file_type VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (Schema::hasColumn('app_downloads', 'title')) {
            Schema::table('app_downloads', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
        // Nullability is intentionally left relaxed on rollback (reverting to NOT NULL
        // would fail if any general-file rows exist).
    }
};
