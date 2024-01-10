<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('loan_id')->nullable(false);
            $table->foreignId('user_id')->nullable(false);
            $table->foreignId('sacco_id')->nullable(false);
            $table->integer('amount')->nullable(false);
            $table->integer('balance')->nullable(false);
            $table->text('description')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_transactions');
    }
}
