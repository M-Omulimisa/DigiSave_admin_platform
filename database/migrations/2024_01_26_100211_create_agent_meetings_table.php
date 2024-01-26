<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentMeetingsTable extends Migration
{
    public function up()
    {
        Schema::create('agent_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('sacco_id');
            $table->date('meeting_date');
            $table->time('meeting_time');
            $table->text('meeting_description')->nullable(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_meetings');
    }
}

