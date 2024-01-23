<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('class_name')->nullable();
            $table->string('use_db_table')->nullable();
            $table->string('table_name')->nullable();
            $table->text('fields')->nullable();
            $table->text('file_id')->nullable();
            $table->string('end_point')->nullable();
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gens');
    }
}

