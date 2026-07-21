<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        if(!Schema::hasTable('social_accounts')){

            Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('social_settings'); // JSON containing all tokens
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Add foreign key to social_posts table
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreignId('social_account_id')->nullable()->after('id')->constrained('social_accounts')->onDelete('cascade');
        });

        }
     
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            if (Schema::hasColumn('social_posts', 'social_account_id')) {
                 $table->dropForeign(['social_account_id']);
                $table->dropColumn('social_account_id');
            }
           
        });
        Schema::dropIfExists('social_accounts');
    }
};
