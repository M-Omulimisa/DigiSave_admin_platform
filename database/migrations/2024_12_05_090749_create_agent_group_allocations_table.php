<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentGroupAllocationsTable extends Migration
{
    public function up()
    {
        Schema::create('agent_group_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agent_id');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreignId('sacco_id')->constrained('saccos')->onDelete('cascade');
            $table->string('status')->default('active');
            $table->timestamp('allocated_at');
            $table->unsignedInteger('allocated_by');
            $table->foreign('allocated_by')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();

            // Add unique constraint to prevent double allocation
            $table->unique(['sacco_id', 'status', 'deleted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_group_allocations');
    }
}
