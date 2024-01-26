<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentAllocationTable extends Migration
{
    public function up()
    {
        // Check if the table already exists before creating it
        if (!Schema::hasTable('agent_allocation')) {
            Schema::create('agent_allocation', function (Blueprint $table) {
                $table->id();
                $table->integer('agent_id')->unsigned();
                $table->foreign('agent_id')->references('id')->on('users');
                // $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('sacco_id')->constrained('saccos');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        // Drop the table if it exists
        Schema::dropIfExists('agent_allocation');
    }
}
