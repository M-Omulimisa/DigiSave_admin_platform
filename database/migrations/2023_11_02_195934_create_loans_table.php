<?php

use App\Models\Sacco;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Sacco::class)->nullable(false);
            $table->foreignId('user_id')->nullable(false);
            $table->foreignId('loan_scheem_id')->nullable(false);
            $table->integer('amount')->nullable(false);
            $table->integer('balance')->nullable(false);
            $table->string('is_fully_paid')->default('no');

            $table->text('scheme_name')->nullable(false);
            $table->text('scheme_description')->nullable(false);
            $table->string('scheme_initial_interest_type')->default('Flat');
            $table->integer('scheme_initial_interest_flat_amount')->nullable();
            $table->integer('scheme_initial_interest_percentage')->nullable();
            $table->string('scheme_bill_periodically')->default('No');
            $table->integer('scheme_billing_period')->nullable();
            $table->string('scheme_periodic_interest_type')->nullable();
            $table->integer('scheme_periodic_interest_percentage')->nullable();
            $table->integer('scheme_periodic_interest_flat_amount')->nullable();
            $table->integer('scheme_min_amount')->nullable(false);
            $table->integer('scheme_max_amount')->nullable(false);
            $table->integer('scheme_min_balance')->nullable(false);
            $table->integer('scheme_max_balance')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
}
