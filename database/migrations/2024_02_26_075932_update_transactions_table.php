<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Make cycle_id nullable
            $table->unsignedBigInteger('cycle_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // If you need to rollback, you can revert the change
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('cycle_id')->change();
        });
    }
}
