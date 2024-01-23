<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSocialFundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('social')) {
            Schema::create('social', function (Blueprint $table) {
                $table->id();
                $table->integer('created_by_id')->unsigned();
                $table->foreign('created_by_id')->references('id')->on('users');
                $table->Integer('user_id')->unsigned();
                $table->foreign('user_id')->references('id')->on('users');
                $table->decimal('amount_paid', 10, 2);
                $table->integer('meeting_number');
                $table->foreignId('cycle_id')->constrained('cycles');
                $table->foreignId('sacco_id')->constrained('saccos');
                
                // Additional columns
                $table->decimal('remaining_balance', 10, 2);
                $table->text('remarks')->nullable();
                // Add other necessary columns here
    
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social');
    }
}




