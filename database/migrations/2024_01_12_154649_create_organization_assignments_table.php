<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('organization_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('sacco_id')->constrained('saccos');

            $table->timestamps();
        });

        // Set a default organization for all saccos
        $defaultOrganizationId = DB::table('organizations')->where('name', 'M-Omulimisa')->value('id');
        $saccoIds = DB::table('saccos')->pluck('id')->toArray();
        
        foreach ($saccoIds as $saccoId) {
            DB::table('organization_assignments')->insert([
                'organization_id' => $defaultOrganizationId,
                'sacco_id' => $saccoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('organization_assignments');
    }
}
