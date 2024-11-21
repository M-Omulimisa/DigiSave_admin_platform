<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('project_sacco', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('sacco_id');
            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('sacco_id')
                ->references('id')
                ->on('saccos')
                ->onDelete('cascade');

            $table->unique(['project_id', 'sacco_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_sacco');
    }
}
