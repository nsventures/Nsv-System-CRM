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


        Schema::table('social_posts', function (Blueprint $table) {
            if(!Schema::hasColumn('social_posts', 'platform_meta')){
                $table->json('platform_meta')->nullable()->after('response_logs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn('social_posts', 'platform_meta')){ 

            Schema::table('social_posts', function (Blueprint $table) {
                $table->dropColumn('platform_meta');
            });
        }
    }
};
