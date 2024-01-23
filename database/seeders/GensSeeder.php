<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GensSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('gens')->insert([
            [
                'id' => 9,
                'created_at' => '2023-07-05 07:23:52',
                'updated_at' => '2023-07-05 07:23:52',
                'class_name' => 'UserModel',
                'use_db_table' => 'Yes',
                'table_name' => 'users',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'users',
            ],
            [
                'id' => 10,
                'created_at' => '2023-08-20 03:43:54',
                'updated_at' => '2023-08-20 03:43:54',
                'class_name' => 'SaccoModel',
                'use_db_table' => 'Yes',
                'table_name' => 'saccos',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'saccos',
            ],
            [
                'id' => 11,
                'created_at' => '2023-11-02 19:24:37',
                'updated_at' => '2023-11-02 19:24:37',
                'class_name' => 'LoanSchemeModel',
                'use_db_table' => 'Yes',
                'table_name' => 'loan_scheems',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'loan-schemes',
            ],
            [
                'id' => 12,
                'created_at' => '2023-11-07 03:26:27',
                'updated_at' => '2023-11-07 03:26:27',
                'class_name' => 'LoanModel',
                'use_db_table' => 'Yes',
                'table_name' => 'loans',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'loans',
            ],
            [
                'id' => 13,
                'created_at' => '2023-12-06 08:25:25',
                'updated_at' => '2023-12-06 08:25:25',
                'class_name' => 'MeetingModel',
                'use_db_table' => 'Yes',
                'table_name' => 'meetings',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'meetings',
            ],
            [
                'id' => 14,
                'created_at' => '2023-12-08 10:29:38',
                'updated_at' => '2023-12-08 10:29:38',
                'class_name' => 'CycleModel',
                'use_db_table' => 'Yes',
                'table_name' => 'cycles',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'cycles',
            ],
            [
                'id' => 15,
                'created_at' => '2023-12-08 11:51:35',
                'updated_at' => '2023-12-08 11:51:35',
                'class_name' => 'ShareRecordModel',
                'use_db_table' => 'Yes',
                'table_name' => 'share_records',
                'fields' => null,
                'file_id' => null,
                'end_point' => 'share-records',
            ],
        ]);
    }
}
