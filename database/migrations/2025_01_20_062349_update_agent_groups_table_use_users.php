<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;            // <-- Make sure this matches your User model namespace
use Encore\Admin\Auth\Database\Administrator;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agent_groups', function (Blueprint $table) {
            // 1. Drop the existing foreign key constraint on 'user_id'
            //    The name of the foreign key might differ depending on how Laravel generated it.
            //    If you're not sure of the exact name, check your database or original migration.
            $table->dropForeign(['user_id']);

            // 2. Drop the 'user_id' column that references administrators
            $table->dropColumn('user_id');

            // 3. Now add a new column referencing the 'users' table
            $table->foreignIdFor(User::class, 'user_id')
                  ->after('id')
                  ->constrained()
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('agent_groups', function (Blueprint $table) {
            // Revert the changes if you rollback

            // 1. Drop the new foreign key referencing users
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // 2. Restore the foreign key referencing administrators
            $table->foreignIdFor(Administrator::class, 'user_id')
                  ->after('id')
                  ->constrained()
                  ->onDelete('cascade');
        });
    }
};
