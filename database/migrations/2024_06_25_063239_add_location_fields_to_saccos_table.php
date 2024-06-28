<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationFieldsToSaccosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saccos', function (Blueprint $table) {
            $table->string('district', 100)->nullable();
            $table->string('subcounty', 100)->nullable();
            $table->string('parish', 100)->nullable();
            $table->string('village', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saccos', function (Blueprint $table) {
            $table->dropColumn(['district', 'subcounty', 'parish', 'village']);
        });
    }
}
