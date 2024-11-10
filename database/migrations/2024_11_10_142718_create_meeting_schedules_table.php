<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meeting_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('location');
            $table->string('district');
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('repeat_option');
            $table->string('notification');
            $table->boolean('notify_group_members')->default(false);
            $table->string('leader_name')->nullable();
            $table->foreignId('sacco_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meeting_schedules');
    }
}
