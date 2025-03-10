<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\AgentAllocation;
use App\Models\District;
use App\Models\OrgAllocation;
use App\Models\Parish;
use App\Models\Sacco;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\Village;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Utils;
use Exception;

class AgentController extends AdminController
{
    protected $title = 'Agents';

    protected function grid()
    {
        $grid = new Grid(new User());

        $admin = Admin::user();
        $adminId = $admin->id;

        $grid->id('ID')->sortable();
        $grid->addColumn('full_name', 'Full Name')->display(function () {
            return $this->first_name . ' ' . $this->last_name;
        })->sortable();
        $grid->phone_number('Phone Number')->sortable();
        $grid->dob('Date of Birth')->sortable();
        $grid->sex('Gender')->sortable();

        // Add new column for group count
        $grid->column('groups_count', 'Agent Groups')->display(function () {
            // Count groups from agent_groups table where user_id matches
            return \App\Models\AgentGroup::where('user_id', $this->id)->count();
        })->sortable();

        // Filter users by user type ID based on AdminRole name 'agent'
        $agentRoleId = AdminRole::where('name', 'agent')->value('id');
        $grid->model()->where('user_type', '=', $agentRoleId);

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('first_name', 'First Name');
        $show->field('last_name', 'Last Name');
        $show->field('phone_number', 'Phone Number');
        $show->field('email', 'Email');
        $show->field('dob', 'Date of Birth');
        $show->field('sex', 'Gender');
        $show->field('district.name', 'District');
        $show->field('subcounty.sub_county', 'Subcounty');
        $show->field('parish.parish_name', 'Parish');
        $show->field('village.village_name', 'Village');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new User());

        $u = Admin::user();

        // Check user permissions
        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        // Basic Information
        $form->tab('Basic Information', function ($form) {
            $form->text('first_name', 'First Name')
                ->rules('required|max:255')
                ->help('Enter agent\'s first name');

            $form->text('last_name', 'Last Name')
                ->rules('required|max:255')
                ->help('Enter agent\'s last name');

            $form->text('phone_number', 'Phone Number')
                ->rules('required|regex:/^[0-9]+$/|min:10|unique:users,phone_number,' . ($form->model()->id ?? ''))
                ->help('Enter valid phone number');

            // Hidden username field
            $form->hidden('username')->default('');

            $form->email('email', 'Email')
                ->rules('nullable|email|unique:users,email,' . ($form->model()->id ?? ''))
                ->help('Enter valid email address');

            $form->date('dob', 'Date of Birth')
                ->rules('required|date|before:today')
                ->help('Select date of birth');

            $form->select('sex', 'Gender')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other'
                ])
                ->rules('required')
                ->help('Select gender');

            // User Type (Role)
            $roles = AdminRole::where('name', 'agent')->pluck('name', 'id');
            $form->select('user_type', 'User Type')
                ->options($roles)
                ->rules('required')
                ->default(function () {
                    return AdminRole::where('name', 'agent')->value('id');
                })
                ->readonly()
                ->help('User type is set to Agent by default');
        });

        // Location Information
        $form->tab('Location Details', function ($form) {
            // District dropdown
            $districtOptions = District::pluck('name', 'id');
            $subcountyOptions = Subcounty::pluck('sub_county', 'id');
            $parishOptions = Parish::pluck('parish_name', 'parish_id');
            $villageOptions = Village::pluck('village_name', 'village_id');

            $form->select('district_id', 'District')
                ->options($districtOptions)
                ->rules('required')
                ->load('subcounty_id', '/api/subcounties');

            // Subcounty dropdown (dynamically loaded)
            $form->select('subcounty_id', 'Subcounty')
                ->options($subcountyOptions)
                ->load('parish_id', '/api/parishes');

            // Parish dropdown (dynamically loaded)
            $form->select('parish_id', 'Parish')
                ->options($parishOptions)
                ->load('village_id', '/api/villages');

            // Village dropdown (dynamically loaded)
            $form->select('village_id', 'Village')
                ->options($villageOptions);
        });

        // Account Credentials
        $form->tab('Account Credentials', function ($form) {
            if ($form->isCreating()) {
                // For new agents
                $form->password('password', 'Password')
                    ->rules('required|min:6|confirmed')
                    ->help('Minimum 6 characters');

                $form->password('password_confirmation', 'Confirm Password')
                    ->rules('required|min:6')
                    ->help('Re-enter password');
            } else {
                // For existing agents
                $form->password('password', 'New Password')
                    ->rules('nullable|min:6|confirmed')
                    ->help('Leave blank to keep current password');

                $form->password('password_confirmation', 'Confirm New Password')
                    ->help('Re-enter new password');
            }
        });

        // Handle password and notifications
        $form->saving(function (Form $form) {
            // Set username to phone_number
            $form->input('username', $form->phone_number);

            // If creating new agent
            if ($form->isCreating()) {
                // Generate random password if not set
                if (empty($form->password)) {
                    $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $form->password = $password;
                    $form->password_confirmation = $password;
                }

                // Store the unhashed password for notifications
                $plainPassword = $form->password;

                // Hash the password
                $form->input('password', Hash::make($form->password));

                // Prepare notification content
                $platformLink = "https://digisave.m-omulimisa.com/";
                $message = "Hello {$form->first_name} {$form->last_name}, you have successfully been registered as an agent. Your login details are: Phone Number: {$form->phone_number}, Password: {$plainPassword}. Click here to access the platform: {$platformLink}";

                $email_info = [
                    "first_name" => $form->first_name,
                    "last_name" => $form->last_name,
                    "phone_number" => $form->phone_number,
                    "password" => $plainPassword,
                    "platformLink" => $platformLink,
                    "org" => "Agent",
                    "email" => $form->email ?? 'info@m-omulimisa.com'
                ];

                try {
                    // Send email if email is provided
                    if (!empty($form->email)) {
                        Mail::to($form->email)->send(new SendMail($email_info));
                        admin_toastr("Email sent successfully to {$form->email}");
                    }

                    // Send SMS
                    $resp = Utils::send_sms($form->phone_number, $message);
                    if ($resp) {
                        admin_toastr("SMS sent successfully to {$form->phone_number}");
                    }
                } catch (Exception $e) {
                    admin_error('Notification failed: ' . $e->getMessage());
                    \Log::error('Agent notification failed: ' . $e->getMessage());
                }
            }
            // If updating existing agent
            else if ($form->password && $form->model()->password != $form->password) {
                $form->input('password', Hash::make($form->password));
            }
        });

        // Ignore confirmation field
        $form->ignore(['password_confirmation']);

        return $form;
    }
}
