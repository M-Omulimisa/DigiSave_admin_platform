<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeleteOnCascadeToCyclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cycles', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['sacco_id']);
            $table->dropForeign(['created_by_id']);

            // Recreate the foreign key constraint with ON DELETE CASCADE
            $table->foreign('sacco_id')
                  ->references('id')
                  ->on('saccos')
                  ->onDelete('cascade');

            $table->foreign('created_by_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            // Drop the foreign key constraints with ON DELETE CASCADE
            $table->dropForeign(['sacco_id']);
            $table->dropForeign(['created_by_id']);

            // Recreate the original foreign key constraints
            $table->foreign('sacco_id')
                  ->references('id')
                  ->on('saccos');

            $table->foreign('created_by_id')
                  ->references('id')
                  ->on('users');
        });
    }
}

