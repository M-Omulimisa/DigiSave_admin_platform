<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCycleIdToLoansTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('loans', 'cycle_id')) {
            Schema::table('loans', function (Blueprint $table) {
                // Add the cycle_id column
                $table->unsignedBigInteger('cycle_id')->nullable();

                // Add foreign key constraint if needed
                $table->foreign('cycle_id')->references('id')->on('cycles')->onDelete('SET NULL');
            });
        }
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            // Drop the foreign key constraint and column
            $table->dropForeign(['cycle_id']);
            $table->dropColumnIfExists('cycle_id');
        });
    }
}

