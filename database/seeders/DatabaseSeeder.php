<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminMenuSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            RoleMenuSeeder::class,
            RolePermissionSeeder::class,
            RoleUserSeeder::class,
            UserPermissionSeeder::class,
            GensSeeder::class,
            DefaultUserSeeder::class,
            OrganizationSeeder::class

        ]);
    }
}
