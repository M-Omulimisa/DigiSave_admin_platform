<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_role_menu')->insert([
            [
                'role_id' => 1,
                'menu_id' => 2,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 13,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 3,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 47,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 47,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 48,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 48,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 49,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 49,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 50,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 51,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 51,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 1,
                'menu_id' => 52,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'role_id' => 2,
                'menu_id' => 52,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);
    }
}
