<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'district_id')) {
                $table->foreignId('district_id')->nullable()->constrained('districts');
            }
            if (!Schema::hasColumn('users', 'parish_id')) {
                $table->foreignId('parish_id')->nullable()->constrained('parishes');
            }
            if (!Schema::hasColumn('users', 'village_id')) {
                $table->foreignId('village_id')->nullable()->constrained('villages');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['parish_id']);
            $table->dropForeign(['village_id']);

            $table->dropColumn('district_id');
            $table->dropColumn('parish_id');
            $table->dropColumn('village_id');
        });
    }
}


