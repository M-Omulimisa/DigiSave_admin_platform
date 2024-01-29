<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_roles')->insert([
            [
                'id' => 1,
                'name' => 'Administrator',
                'slug' => 'admin',
                'created_at' => '2022-08-25 07:26:35',
                'updated_at' => '2022-08-25 07:26:35',
            ],
            [
                'id' => 2,
                'name' => 'Sacco Admin',
                'slug' => 'sacco',
                'created_at' => '2022-08-25 08:43:46',
                'updated_at' => '2023-12-06 19:26:10',
            ],
            [
                'id' => 3,
                'name' => 'Sacco Member',
                'slug' => 'member',
                'created_at' => '2023-12-06 19:27:20',
                'updated_at' => '2023-12-06 19:27:20',
            ],
            [
                'id' => 4,
                'name' => 'agent',
                'slug' => 'agent',
                'created_at' => '2023-12-07 19:27:20',
                'updated_at' => '2023-12-07 19:27:20',
            ],
            [
                'id' => 5,
                'name' => 'org',
                'slug' => 'org',
                'created_at' => '2023-12-07 19:27:20',
                'updated_at' => '2023-12-07 19:27:20',
            ]
        ]);
    }
}
