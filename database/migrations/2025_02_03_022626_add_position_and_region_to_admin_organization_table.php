<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_organization', function (Blueprint $table) {
            $table->string('position')->nullable()->after('vsla_organisation_id');
            $table->string('region')->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_organization', function (Blueprint $table) {
            $table->dropColumn(['position', 'region']);
        });
    }
};
