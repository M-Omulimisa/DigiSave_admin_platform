<?php

use App\Models\Sacco;
use Illuminate\Database\Migrations\Migration;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('agent_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Administrator::class, 'user_id');
            $table->foreignIdFor(Sacco::class, 'sacco_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_groups');
    }
}
