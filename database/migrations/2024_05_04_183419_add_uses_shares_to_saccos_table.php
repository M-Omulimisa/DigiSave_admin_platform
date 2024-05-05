<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsesSharesToSaccosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saccos', function (Blueprint $table) {
            // Add a boolean column for uses_shares, defaulting to false
            $table->boolean('uses_shares')->default(false);
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
            // Remove the uses_shares column if the migration is rolled back
            $table->dropColumn('uses_shares');
        });
    }
}
