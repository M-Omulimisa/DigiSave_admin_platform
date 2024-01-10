<?php

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('name')->nullable();
            $table->date('date')->nullable();
            $table->text('location')->nullable();
            $table->foreignId('sacco_id')->nullable();
            $table->foreignIdFor(Administrator::class)->nullable();
            $table->text('members')->nullable();
            $table->text('minutes')->nullable();
            $table->text('attendance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meetings');
    }
}
