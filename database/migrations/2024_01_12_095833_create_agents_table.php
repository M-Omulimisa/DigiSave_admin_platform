<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('phone_number');
                $table->string('email')->nullable();
                $table->date('date_of_birth');
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('national_id')->nullable();
                
                // Foreign keys
                $table->foreignId('district_id')->nullable()->constrained('districts');
                $table->foreignId('subcounty_id')->nullable()->constrained('subcounties');
                $table->foreignId('parish_id')->nullable()->constrained('parishes');
                $table->foreignId('village_id')->nullable()->constrained('villages');
                
                $table->timestamps();
            });
        }
    }

    public function down()
    {
    }
}

