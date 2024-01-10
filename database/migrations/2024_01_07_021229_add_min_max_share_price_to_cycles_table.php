<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMinMaxSharePriceToCyclesTable extends Migration
{
    public function up()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->integer('min_share_price')->nullable();
            $table->integer('max_share_price')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('min_share_price');
            $table->dropColumn('max_share_price');
        });
    }
}
