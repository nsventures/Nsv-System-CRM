<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable('social_posts')){

       
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('caption')->nullable();
            $table->json('platforms');
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['pending', 'scheduled', 'published','failed', 'partially_published'])->default('pending');
            $table->text('response_logs')->nullable();
            $table->timestamps();
        });


            // Insert permissions manually only if not exists
            $permissions = ['manage_posts', 'create_posts', 'edit_posts', 'delete_posts'];

            foreach ($permissions as $permission) {
                if (!DB::table('permissions')->where('name', $permission)->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $permission,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

             }
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');

        //Remove permissions if migration is rolled back

        DB::table('permissions')->whereIn('name', [
            'manage_posts',
            'create_posts',
            'edit_posts',
            'delete_posts',
        ])->delete();
    }
};
