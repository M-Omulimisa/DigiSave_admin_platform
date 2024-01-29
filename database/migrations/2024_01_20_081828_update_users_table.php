<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 45)->nullable();
            $table->string('last_name', 45)->nullable();
            $table->string('reg_date', 45)->nullable();
            $table->string('last_seen', 45)->nullable();
            $table->string('email', 45)->nullable();
            $table->tinyInteger('approved')->nullable();
            $table->string('profile_photo', 255)->nullable();
            $table->string('user_type', 45)->nullable();
            $table->string('sex', 25)->nullable();
            $table->string('reg_number', 50)->nullable();
            $table->string('country', 35)->nullable();
            $table->string('occupation', 225)->nullable();
            $table->text('profile_photo_large')->nullable();
            $table->string('phone_number', 35)->nullable();
            $table->string('location_lat', 45)->nullable();
            $table->string('location_long', 45)->nullable();
            $table->string('facebook', 500)->nullable();
            $table->string('twitter', 500)->nullable();
            $table->string('whatsapp', 45)->nullable();
            $table->string('linkedin', 500)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('other_link', 500)->nullable();
            $table->string('cv', 500)->nullable();
            $table->string('language', 50)->nullable();
            $table->string('about', 600)->nullable();
            $table->string('address', 325)->nullable();
            $table->string('campus_id', 255)->nullable();
            $table->tinyInteger('complete_profile')->nullable();
            $table->string('title', 20)->nullable();
            $table->timestamp('dob')->nullable();
            $table->text('intro')->nullable();
            $table->integer('sacco_id')->nullable();
            $table->string('sacco_join_status', 35)->nullable()->default('No Sacco');
            $table->text('id_front')->nullable();
            $table->text('id_back')->nullable();
            $table->string('status', 25)->default('Active');
            $table->integer('balance')->default(0);
            $table->foreignId('district_id')->nullable()->constrained('districts');
            $table->foreignId('subcounty_id')->nullable()->constrained('subcounties');
            $table->unsignedBigInteger('parish_id')->nullable();
            $table->foreign('parish_id')->references('parish_id')->on('parishes');
            $table->unsignedBigInteger('village_id')->nullable();
            $table->foreign('village_id')->references('village_id')->on('villages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'reg_date', 'last_seen',
                'email', 'approved', 'profile_photo', 'user_type', 'sex', 'reg_number',
                'country', 'occupation', 'profile_photo_large', 'phone_number', 'location_lat',
                'location_long', 'facebook', 'twitter', 'whatsapp', 'linkedin', 'website',
                'other_link', 'cv', 'language', 'about', 'address',
                 'campus_id', 'complete_profile', 'title', 'dob', 'intro', 'sacco_id',
                'sacco_join_status', 'id_front', 'id_back', 'status', 'balance',
                'district_id', 'subcounty_id', 'parish_id', 'village_id', 'pwd'
            ]);
        });
    }
}
