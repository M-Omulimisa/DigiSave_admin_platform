<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeAllocatedByNullableOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Direct SQL to modify the column to allow NULL
        DB::statement('ALTER TABLE agent_group_allocations MODIFY allocated_by BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Direct SQL to change back to NOT NULL
        DB::statement('ALTER TABLE agent_group_allocations MODIFY allocated_by BIGINT UNSIGNED NOT NULL');
    }
}
