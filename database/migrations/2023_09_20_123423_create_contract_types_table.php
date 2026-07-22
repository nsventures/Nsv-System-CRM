<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTypesTable extends Migration
{
    public function up()
    {
        // Guarded: this migration was renamed, so existing deployments (where the
        // table was already created under the old filename) would otherwise fail
        // with "table already exists".
        if (Schema::hasTable('contract_types')) {
            return;
        }

        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('type'); // Add a column for the contract type name
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_types');
    }
}
