<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_user_permissions')->insert([
            [
                'user_id' => 111,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'user_id' => 113,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
