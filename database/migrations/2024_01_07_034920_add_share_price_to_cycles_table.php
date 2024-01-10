<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSharePriceToCyclesTable extends Migration
{
    public function up()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->integer('share_price')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('share_price');
        });
    }
}

