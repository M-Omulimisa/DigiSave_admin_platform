<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DropAllocatedByForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Using raw SQL to ensure the constraint is dropped properly
        DB::statement('ALTER TABLE agent_group_allocations DROP FOREIGN KEY agent_group_allocations_allocated_by_foreign');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_group_allocations', function (Blueprint $table) {
            $table->foreign('allocated_by', 'agent_group_allocations_allocated_by_foreign')
                  ->references('id')
                  ->on('users');
        });
    }
}
