<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_role_users')->insert([
            [
                'role_id' => 1,
                'user_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 5,
                'user_id' => 2,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
