<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountRequiredPerMeetingToCyclesTable extends Migration
{
    public function up()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->decimal('amount_required_per_meeting', 10, 2)->default(3000.00);
        });
    }

    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('amount_required_per_meeting');
        });
    }
}


