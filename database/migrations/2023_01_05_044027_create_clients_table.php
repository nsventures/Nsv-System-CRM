<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('country_code')->nullable();
            $table->string('country_iso_code')->nullable();
            $table->string('password');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip')->nullable();
            $table->string('photo')->nullable();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->tinyInteger('status')->default(1)->nullable();
            $table->string('lang')->default('en')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('acct_create_mail_sent')->default(0)->nullable();
            $table->tinyInteger('email_verification_mail_sent')->default(0)->nullable();
            $table->text('internal_purpose')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
