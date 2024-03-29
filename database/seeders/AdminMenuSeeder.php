<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_menu')->insert([
            [
                'id' => 1,
                'parent_id' => 0,
                'order' => 1,
                'title' => 'Dashboard',
                'icon' => 'fa-bar-chart',
                'uri' => '/',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            [
                'id' => 2,
                'parent_id' => 0,
                'order' => 19,
                'title' => 'Admin',
                'icon' => 'fa-tasks',
                'uri' => '',
                'permission' => null,
                'created_at' => '2023-12-07 01:47:55',
                'updated_at' => null,
            ],
            [
                'id' => 3,
                'parent_id' => 2,
                'order' => 18,
                'title' => 'System Users',
                'icon' => 'fa-users',
                'uri' => 'auth/users',
                'permission' => null,
                'created_at' => '2023-12-07 01:47:55',
                'updated_at' => null,
            ],
            [
                'id' => 7,
                'parent_id' => 2,
                'order' => 17,
                'title' => 'Operation log',
                'icon' => 'fa-history',
                'uri' => 'auth/logs',
                'permission' => null,
                'created_at' => '2023-12-07 01:47:55',
                'updated_at' => null,
            ],
            [
                'id' => 13,
                'parent_id' => 2,
                'order' => 16,
                'title' => 'News Post Categories',
                'icon' => 'fa-align-center',
                'uri' => 'post-categories',
                'permission' => null,
                'created_at' => '2023-01-01 15:58:24',
                'updated_at' => '2023-12-07 01:47:55',
            ],
            [
                'id' => 19,
                'parent_id' => 0,
                'order' => 15,
                'title' => 'Edit my profile',
                'icon' => 'fa-edit',
                'uri' => 'auth/setting',
                'permission' => null,
                'created_at' => '2023-01-02 09:32:35',
                'updated_at' => '2023-12-07 01:47:55',
            ],
            [
                'id' => 47,
                'parent_id' => 0,
                'order' => 14,
                'title' => 'VSLA Groups',
                'icon' => 'fa-group',
                'uri' => 'saccos',
                'permission' => null,
                'created_at' => '2023-12-06 19:36:15',
                'updated_at' => '2023-12-07 01:47:55',
            ],
            [
                'id' => 48,
                'parent_id' => 0,
                'order' => 13,
                'title' => 'VSLA members',
                'icon' => 'fa-th-list',
                'uri' => 'members',
                'permission' => null,
                'created_at' => '2023-12-06 22:04:41',
                'updated_at' => '2023-12-07 01:47:55',
            ],
            // [
            //     'id' => 49,
            //     'parent_id' => 0,
            //     'order' => 12,
            //     'title' => 'Cycles',
            //     'icon' => 'fa-recycle',
            //     'uri' => 'cycles',
            //     'permission' => null,
            //     'created_at' => '2023-12-06 22:13:22',
            //     'updated_at' => '2023-12-07 01:47:55',
            // ],
            [
                'id' => 50,
                'parent_id' => 0,
                'order' => 8,
                'title' => 'Shares',
                'icon' => 'fa-pie-chart',
                'uri' => 'share-records',
                'permission' => null,
                'created_at' => '2023-12-06 22:49:18',
                'updated_at' => '2023-12-07 01:47:55',
            ],
            [
                'id' => 51,
                'parent_id' => 0,
                'order' => 9,
                'title' => 'Transactions',
                'icon' => 'fa-money',
                'uri' => 'transactions',
                'permission' => null,
                'created_at' => '2023-12-06 23:47:30',
                'updated_at' => '2023-12-07 01:48:08',
            ],
            // [
            //     'id' => 52,
            //     'parent_id' => 0,
            //     'order' => 10,
            //     'title' => 'Loan Schemes',
            //     'icon' => 'fa-bank',
            //     'uri' => 'loan-scheems',
            //     'permission' => null,
            //     'created_at' => '2023-12-07 00:25:40',
            //     'updated_at' => '2023-12-07 01:48:08',
            // ],
            // [
            //     'id' => 53,
            //     'parent_id' => 0,
            //     'order' => 11,
            //     'title' => 'Meetings',
            //     'icon' => 'fa fa-address-card',
            //     'uri' => 'meetings',
            //     'permission' => null,
            //     'created_at' => '2023-12-07 01:47:45',
            //     'updated_at' => '2023-12-07 01:48:08',
            // ],
            [
                'id' => 54,
                'parent_id' => 0,
                'order' => 2,
                'title' => 'Village Agents',
                'icon' => 'fa-user-circle',
                'uri' => 'agents',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            [
                'id' => 55,
                'parent_id' => 0,
                'order' => 3,
                'title' => 'Assign Agents',
                'icon' => 'fa-random',
                'uri' => 'assign-agent',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            [
                'id' => 56,
                'parent_id' => 0,
                'order' => 4,
                'title' => 'Organisation Admin',
                'icon' => 'fa-tag',
                'uri' => 'org-admin',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            [
                'id' => 57,
                'parent_id' => 0,
                'order' => 5,
                'title' => 'Organisation',
                'icon' => 'fa-users',
                'uri' => 'organisation',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            [
                'id' => 58,
                'parent_id' => 0,
                'order' => 6,
                'title' => 'Assign Organisation',
                'icon' => 'fa-tag',
                'uri' => 'assign-org',
                'permission' => null,
                'created_at' => '2023-07-03 05:13:53',
                'updated_at' => null,
            ],
            // [
            //     'id' => 59,
            //     'parent_id' => 0,
            //     'order' => 7,
            //     'title' => 'Allocate Org Admin',
            //     'icon' => 'fa-tag',
            //     'uri' => 'assign-org-admin',
            //     'permission' => null,
            //     'created_at' => '2023-07-03 05:13:53',
            //     'updated_at' => null,
            // ],
        ]);
    }
}
