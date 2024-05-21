<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdatePositionsTableMakeSaccoIdNullableAndInsertTreasurer extends Migration
{
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            // Make sacco_id nullable
            $table->unsignedBigInteger('sacco_id')->nullable()->change();
        });

        // Insert default position
        DB::table('positions')->insert([
            'name' => 'Treasurer',
            'sacco_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::table('positions', function (Blueprint $table) {
            // Revert sacco_id to not nullable
            $table->unsignedBigInteger('sacco_id')->nullable(false)->change();
        });

        // Delete the inserted default position
        DB::table('positions')->where('name', 'Treasurer')->delete();
    }
}
