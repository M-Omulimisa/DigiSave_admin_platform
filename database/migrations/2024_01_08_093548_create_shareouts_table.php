<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareoutsTable extends Migration
{
    public function up()
    {
        Schema::create('shareouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sacco_id')->constrained('saccos');
            $table->foreignId('cycle_id')->constrained('cycles');
            $table->foreignId('member_id')->constrained('members');
            $table->decimal('shareout_amount', 10, 2);
            $table->date('shareout_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shareouts');
    }
}

