<?php

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaccosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saccos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Administrator::class)->default(1);
            $table->text('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('establishment_date')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('chairperson_name')->nullable();
            $table->string('chairperson_phone_number')->nullable();
            $table->string('chairperson_email_address')->nullable();
            $table->text('about')->nullable();
            $table->text('terms')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saccos');
    }
}
