<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomPositionsTable extends Migration
{
    public function up()
    {
        Schema::create('custom_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('sacco_id')->constrained('saccos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_positions');
    }
}
