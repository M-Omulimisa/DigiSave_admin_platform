<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEligibleMembersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('eligible_members')) {
            Schema::create('eligible_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sacco_id')->constrained('saccos')->onDelete('cascade');
                $table->foreignId('active_cycle_id')->constrained('cycles')->onDelete('cascade');
                $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('scheme_id')->constrained('loan_schemes')->onDelete('cascade');
                $table->decimal('max_eligible_amount', 10, 2); // Adjust the precision and scale as needed
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('eligible_members', 'sacco_id')) {
                Schema::table('eligible_members', function (Blueprint $table) {
                    $table->foreignId('sacco_id')->constrained('saccos')->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn('eligible_members', 'active_cycle_id')) {
                Schema::table('eligible_members', function (Blueprint $table) {
                    $table->foreignId('active_cycle_id')->constrained('cycles')->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn('eligible_members', 'member_id')) {
                Schema::table('eligible_members', function (Blueprint $table) {
                    $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn('eligible_members', 'scheme_id')) {
                Schema::table('eligible_members', function (Blueprint $table) {
                    $table->foreignId('scheme_id')->constrained('loan_schemes')->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn('eligible_members', 'max_eligible_amount')) {
                Schema::table('eligible_members', function (Blueprint $table) {
                    $table->decimal('max_eligible_amount', 10, 2); // Adjust the precision and scale as needed
                });
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('eligible_members');
    }
}

