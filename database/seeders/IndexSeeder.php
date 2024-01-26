<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Indexes for admin_menu table
        DB::statement('ALTER TABLE `admin_menu` ADD PRIMARY KEY (`id`)');

        // Indexes for admin_operation_log table
        DB::statement('ALTER TABLE `admin_operation_log` ADD PRIMARY KEY (`id`), ADD KEY `admin_operation_log_user_id_index` (`user_id`)');

        // Indexes for admin_permissions table
        DB::statement('ALTER TABLE `admin_permissions` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `admin_permissions_name_unique` (`name`), ADD UNIQUE KEY `admin_permissions_slug_unique` (`slug`)');

        // Indexes for admin_roles table
        DB::statement('ALTER TABLE `admin_roles` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `admin_roles_name_unique` (`name`), ADD UNIQUE KEY `admin_roles_slug_unique` (`slug`)');

        // Indexes for admin_role_menu table
        DB::statement('ALTER TABLE `admin_role_menu` ADD KEY `admin_role_menu_role_id_menu_id_index` (`role_id`,`menu_id`)');

        // Indexes for admin_role_permissions table
        DB::statement('ALTER TABLE `admin_role_permissions` ADD KEY `admin_role_permissions_role_id_permission_id_index` (`role_id`,`permission_id`)');

        // Indexes for admin_role_users table
        DB::statement('ALTER TABLE `admin_role_users` ADD KEY `admin_role_users_role_id_user_id_index` (`role_id`,`user_id`)');

        // Indexes for admin_user_permissions table
        DB::statement('ALTER TABLE `admin_user_permissions` ADD KEY `admin_user_permissions_user_id_permission_id_index` (`user_id`,`permission_id`)');

        // Indexes for company table
        DB::statement('ALTER TABLE `company` ADD PRIMARY KEY (`company_id`)');

        // Indexes for cycles table
        DB::statement('ALTER TABLE `cycles` ADD PRIMARY KEY (`id`)');

        // Indexes for districts table
        DB::statement('ALTER TABLE `districts` ADD PRIMARY KEY (`id`)');

        // Indexes for failed_jobs table
        DB::statement('ALTER TABLE `failed_jobs` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)');

        // Indexes for feedback table
        DB::statement('ALTER TABLE `feedback` ADD PRIMARY KEY (`feedback_id`)');

        // Indexes for gens table
        DB::statement('ALTER TABLE `gens` ADD PRIMARY KEY (`id`)');

        // Indexes for groups table
        DB::statement('ALTER TABLE `groups` ADD PRIMARY KEY (`id`)');

        // Indexes for loans table
        DB::statement('ALTER TABLE `loans` ADD PRIMARY KEY (`id`)');

        // Indexes for loan_scheems table
        DB::statement('ALTER TABLE `loan_scheems` ADD PRIMARY KEY (`id`)');

        // Indexes for loan_transactions table
        DB::statement('ALTER TABLE `loan_transactions` ADD PRIMARY KEY (`id`)');

        // Indexes for locations table
        DB::statement('ALTER TABLE `locations` ADD PRIMARY KEY (`id`)');

        // Indexes for meetings table
        DB::statement('ALTER TABLE `meetings` ADD PRIMARY KEY (`id`)');

        // Indexes for migrations table
        DB::statement('ALTER TABLE `migrations` ADD PRIMARY KEY (`id`)');

        // Indexes for participants table
        DB::statement('ALTER TABLE `participants` ADD PRIMARY KEY (`id`)');

        // Indexes for password_resets table
        DB::statement('ALTER TABLE `password_resets` ADD KEY `password_resets_email_index` (`email`)');

        // Indexes for personal_access_tokens table
        DB::statement('ALTER TABLE `personal_access_tokens` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`), ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)');

        // Indexes for saccos table
        DB::statement('ALTER TABLE `saccos` ADD PRIMARY KEY (`id`)');

        // Indexes for share_records table
        DB::statement('ALTER TABLE `share_records` ADD PRIMARY KEY (`id`)');

        // Indexes for traffic_records table
        DB::statement('ALTER TABLE `traffic_records` ADD PRIMARY KEY (`id`)');

        // Indexes for transactions table
        DB::statement('ALTER TABLE `transactions` ADD PRIMARY KEY (`id`)');

        // Indexes for uploads table
        DB::statement('ALTER TABLE `uploads` ADD PRIMARY KEY (`upload_id`)');

        // Indexes for users table
        DB::statement('ALTER TABLE `users` ADD PRIMARY KEY (`id`)');

        // Indexes for user_has_program table
        DB::statement('ALTER TABLE `user_has_program` ADD PRIMARY KEY (`id`)');

        // AUTO_INCREMENT adjustments for dumped tables...

        // AUTO_INCREMENT for table admin_menu
        DB::statement('ALTER TABLE `admin_menu` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54');

        // AUTO_INCREMENT for table admin_operation_log
        DB::statement('ALTER TABLE `admin_operation_log` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT');

        // AUTO_INCREMENT for table admin_permissions
        DB::statement('ALTER TABLE `admin_permissions` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6');

        // AUTO_INCREMENT for table admin_roles
        DB::statement('ALTER TABLE `admin_roles` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4');

        // AUTO_INCREMENT for table cycles
        DB::statement('ALTER TABLE `cycles` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9');

        // AUTO_INCREMENT for table failed_jobs
        DB::statement('ALTER TABLE `failed_jobs` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT');

        // AUTO_INCREMENT for table feedback
        DB::statement('ALTER TABLE `feedback` MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5');

        // AUTO_INCREMENT for table gens
        DB::statement('ALTER TABLE `gens` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16');

        // AUTO_INCREMENT for table groups
        DB::statement('ALTER TABLE `groups` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110');

        // AUTO_INCREMENT for table loans
        DB::statement('ALTER TABLE `loans` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38');

        // AUTO_INCREMENT for table loan_scheems
        DB::statement('ALTER TABLE `loan_scheems` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6');

        // AUTO_INCREMENT for table loan_transactions
        DB::statement('ALTER TABLE `loan_transactions` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48');

        // AUTO_INCREMENT for table locations
        DB::statement('ALTER TABLE `locations` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002007');

        // AUTO_INCREMENT for table meetings
        DB::statement('ALTER TABLE `meetings` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3');

        // AUTO_INCREMENT for table migrations
        DB::statement('ALTER TABLE `migrations` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44');

        // AUTO_INCREMENT for table participants
        DB::statement('ALTER TABLE `participants` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT');

        // AUTO_INCREMENT for table personal_access_tokens
        DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT');

        // AUTO_INCREMENT for table saccos
        DB::statement('ALTER TABLE `saccos` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5');

        // AUTO_INCREMENT for table share_records
        DB::statement('ALTER TABLE `share_records` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13');

        // AUTO_INCREMENT for table traffic_records
        DB::statement('ALTER TABLE `traffic_records` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT');

        // AUTO_INCREMENT for table transactions
        DB::statement('ALTER TABLE `transactions` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182');

        // AUTO_INCREMENT for table uploads
        DB::statement('ALTER TABLE `uploads` MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94');

        // AUTO_INCREMENT for table users
        DB::statement('ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120');

        // AUTO_INCREMENT for table user_has_program
        DB::statement('ALTER TABLE `user_has_program` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76');
    }
}
