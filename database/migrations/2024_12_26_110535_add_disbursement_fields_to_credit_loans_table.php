<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisbursementFieldsToCreditLoansTable extends Migration
{
    public function up()
    {
        Schema::table('credit_loans', function (Blueprint $table) {
            $table->enum('disbursement_status', ['pending', 'disbursed'])->default('pending');
            $table->timestamp('disbursed_at')->nullable();
            $table->string('disbursement_reference')->nullable();
        });
    }

    public function down()
    {
        Schema::table('credit_loans', function (Blueprint $table) {
            $table->dropColumn(['disbursement_status', 'disbursed_at', 'disbursement_reference']);
        });
    }
}
