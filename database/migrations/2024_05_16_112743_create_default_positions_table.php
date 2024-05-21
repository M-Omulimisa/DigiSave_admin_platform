<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDefaultPositionsTable extends Migration
{
    public function up()
    {
        Schema::create('default_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insert default positions
        DB::table('default_positions')->insert([
            ['name' => 'Chairperson'],
            ['name' => 'Secretary'],
            ['name' => 'Treasurer']
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('default_positions');
    }
}
