<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCycleIdToLoanTransactionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('loan_transactions', 'cycle_id')) {
            Schema::table('loan_transactions', function (Blueprint $table) {
                // Add cycle_id column
                $table->ForeignId('cycle_id')->nullable()->constrained();

                // // Add foreign key constraint if necessary
                // $table->foreign('cycle_id')->references('id')->on('cycles')->onDelete('SET NULL');
            });
        }
    }

    public function down()
    {
        Schema::table('loan_transactions', function (Blueprint $table) {
            // Drop the cycle_id column if it exists when rolling back the migration
            $table->dropColumnIfExists('cycle_id');
        });
    }
}


