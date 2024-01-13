<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareoutsTable extends Migration
{
    public function up()
    {
        $tableName = 'shareouts';

        // Check if the table already exists
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('sacco_id')->constrained('saccos');
                $table->foreignId('cycle_id')->constrained('cycles');
                $table->foreignId('member_id')->constrained('members');
                $table->decimal('shareout_amount', 10, 2);
                $table->date('shareout_date');
                $table->timestamps();
            });
        } else {
            // Table already exists, you can handle this case if needed
            echo "Table '$tableName' already exists.\n";
        }

        // Check if the 'shareout_amount' column already exists
        if (!Schema::hasColumn($tableName, 'shareout_amount')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('shareout_amount', 10, 2);
            });
        } else {
            // Column already exists, you can handle this case if needed
            echo "Column 'shareout_amount' already exists in table '$tableName'.\n";
        }

        // Check if the 'shareout_date' column already exists
        if (!Schema::hasColumn($tableName, 'shareout_date')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->date('shareout_date');
            });
        } else {
            // Column already exists, you can handle this case if needed
            echo "Column 'shareout_date' already exists in table '$tableName'.\n";
        }
    }

    public function down()
    {
        // Do nothing in the down method
        // Schema::dropIfExists('shareouts');
    }
}


