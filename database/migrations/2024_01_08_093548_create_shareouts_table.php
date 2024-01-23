<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareoutsTable extends Migration
{
    public function up()
    {
        $tableName = 'shareouts';

        // Check if the table already exists
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('sacco_id')->constrained('saccos');
                $table->foreignId('cycle_id')->constrained('cycles');
                $table->integer('member_id')->unsigned();
                $table->foreign('member_id')->references('id')->on('users');
                // $table->foreignId('member_id')->constrained('members');
                $table->decimal('shareout_amount', 10, 2);
                $table->date('shareout_date');
                $table->timestamps();
            });
       
    }

    public function down()
    {
        // Do nothing in the down method
        // Schema::dropIfExists('shareouts');
    }
}


