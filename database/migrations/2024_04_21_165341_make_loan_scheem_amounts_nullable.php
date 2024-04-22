<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeLoanScheemAmountsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_scheems', function (Blueprint $table) {
            $table->integer('min_amount')->nullable()->change();
            $table->integer('max_amount')->nullable()->change();
            $table->integer('min_balance')->nullable()->change();
            $table->integer('max_balance')->nullable()->change();
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
            $table->integer('min_amount')->nullable(false)->change();
            $table->integer('max_amount')->nullable(false)->change();
            $table->integer('min_balance')->nullable(false)->change();
            $table->integer('max_balance')->nullable(false)->change();
        });
    }
}
