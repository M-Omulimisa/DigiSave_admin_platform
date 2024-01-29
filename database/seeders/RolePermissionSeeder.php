<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_role_permissions')->insert([
            [
                'role_id' => 1,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 3,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 5,
                'permission_id' => 1,
                'created_at' => null,
                'updated_at' => null,
            ]
        ]);
    }
}
