<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberCycleDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('member_cycle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sacco_id')->constrained('saccos');
            $table->foreignId('cycle_id')->constrained('cycles');
            $table->string('name');
            $table->decimal('shares', 10, 2)->default(0);
            $table->decimal('loans', 10, 2)->default(0);
            $table->decimal('loan_repayments', 10, 2)->default(0);
            $table->decimal('fines', 10, 2)->default(0);
            $table->decimal('share_out_money', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_cycle_details');
    }
}
