<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRepaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_loan_id')->constrained('credit_loans')->onDelete('cascade');
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('principal_paid', 15, 2);
            $table->decimal('interest_paid', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->enum('payment_method', ['bank', 'airtel', 'mtn']);
            $table->string('transaction_reference');
            $table->string('payment_proof')->nullable();
            $table->text('notes')->nullable();
            $table->string('received_by');
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamp('payment_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_repayments');
    }
}
