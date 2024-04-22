<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequiredSavingsPercentageToLoanScheemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_scheems', function (Blueprint $table) {
            $table->integer('savings_percentage')->nullable(); // Adding the new column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loan_scheems', function (Blueprint $table) {
            $table->dropColumn('savings_percentage'); // Removing the column if migration is rolled back
        });
    }
}
