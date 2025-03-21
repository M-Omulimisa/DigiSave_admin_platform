<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingCollsToLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->bigInteger('principal_amount')->nullable()->default(0);
            $table->bigInteger('amount_paid')->nullable()->default(0);
            $table->bigInteger('amount_not_paid')->nullable()->default(0);
            $table->string('is_processed')->nullable()->default('No');
            $table->string('sex_of_beneficiary')->nullable();
            $table->string('is_refugee')->nullable()->default('No'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            //
        });
    }
}
