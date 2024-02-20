<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaOrgSaccoTable extends Migration

{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_organisation_sacco', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vsla_organisation_id')->constrained('vsla_organisations');
            $table->foreignId('sacco_id')->constrained('saccos');
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
        Schema::dropIfExists('vsla_organisation_sacco');
    }
}

