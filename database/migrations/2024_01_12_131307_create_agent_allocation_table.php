<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentAllocationTable extends Migration
{
    public function up()
    {
        Schema::create('agent_allocation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('sacco_id')->constrained('saccos')->onDelete('cascade');
            // You can add additional fields if needed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_allocation');
    }
}
