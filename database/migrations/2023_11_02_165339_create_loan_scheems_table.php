<?php

use App\Models\Sacco;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanScheemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_scheems', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Sacco::class)->nullable(false);
            $table->text('name')->nullable(false);
            $table->text('description')->nullable(false);
            $table->string('initial_interest_type')->default('Flat');
            $table->integer('initial_interest_flat_amount')->nullable();
            $table->integer('initial_interest_percentage')->nullable();
            $table->string('bill_periodically')->default('No');
            $table->integer('billing_period')->nullable();
            $table->string('periodic_interest_type')->nullable();
            $table->integer('periodic_interest_percentage')->nullable();
            $table->integer('periodic_interest_flat_amount')->nullable();
            $table->integer('min_amount')->nullable(false);
            $table->integer('max_amount')->nullable(false);
            $table->integer('min_balance')->nullable(false);
            $table->integer('max_balance')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_scheems');
    }
}
