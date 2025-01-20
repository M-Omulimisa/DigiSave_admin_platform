<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Sacco;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agent_groups_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable(false);
            $table->foreignId('sacco_id')->nullable(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_groups_users');
    }
};
