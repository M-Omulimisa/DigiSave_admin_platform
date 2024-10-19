<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditLoansTable extends Migration
{
    public function up()
    {
        Schema::create('credit_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sacco_id')->constrained('saccos');
            $table->decimal('loan_amount', 15, 2);
            $table->integer('loan_term');
            $table->decimal('total_interest', 15, 2);
            $table->decimal('monthly_payment', 15, 2);
            $table->string('loan_purpose');
            $table->string('billing_address');
            $table->enum('selected_method', ['bank', 'airtel', 'mtn']);
            $table->string('selected_bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name');
            $table->string('phone_number')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('use_current_address')->default(false);
            $table->enum('loan_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_loans');
    }
}
