<?php

use App\Models\Sacco;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Administrator::class, 'user_id');
            $table->foreignIdFor(Administrator::class, 'source_user_id');
            $table->foreignIdFor(Sacco::class, 'sacco_id');
            $table->string('type')->default('Deposit');
            $table->string('source_type')->default('Mobile Money');
            $table->string('source_mobile_money_number')->nullable();
            $table->string('source_mobile_money_transaction_id')->nullable();
            $table->string('source_bank_account_number')->nullable();
            $table->string('source_bank_transaction_id')->nullable();
            $table->string('desination_type')->default('Mobile Money');
            $table->string('desination_mobile_money_number')->nullable();
            $table->string('desination_mobile_money_transaction_id')->nullable();
            $table->string('desination_bank_account_number')->nullable();
            $table->string('desination_bank_transaction_id')->nullable();
            $table->string('amount');
            $table->text('description')->nullable();
            $table->text('details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
