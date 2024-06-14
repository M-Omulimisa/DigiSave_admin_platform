<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeysOnVslaOrganisationSaccoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vsla_organisation_sacco', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['vsla_organisation_id']);
            $table->dropForeign(['sacco_id']);

            // Add new foreign key constraints with onDelete('cascade')
            $table->foreign('vsla_organisation_id')->references('id')->on('vsla_organisations')->onDelete('cascade');
            $table->foreign('sacco_id')->references('id')->on('saccos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vsla_organisation_sacco', function (Blueprint $table) {
            // Drop the new foreign key constraints
            $table->dropForeign(['vsla_organisation_id']);
            $table->dropForeign(['sacco_id']);

            // Restore the original foreign key constraints without onDelete('cascade')
            $table->foreign('vsla_organisation_id')->references('id')->on('vsla_organisations');
            $table->foreign('sacco_id')->references('id')->on('saccos');
        });
    }
}
