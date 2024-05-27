<?php

namespace App\Admin\Controllers;

use App\Mail\SendMail;
use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\District;
use App\Models\OrgAllocation;
use App\Models\Parish;
use App\Models\Sacco;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\Village;
use App\Models\VslaOrganisation;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OrganisationAdminController extends AdminController
{
    protected $title = 'Organisation Admin';

    protected function grid()
    {
        $grid = new Grid(new User());

        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');

        if (!is_string($sortOrder)) {
            $sortOrder = 'desc';
        }

        if (!$admin->isRole('admin')) {
            // Get the organisation allocation for the logged-in admin
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();

            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;

                // Get all users allocated to the same organisation
                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgId)->pluck('user_id')->toArray();

                // Filter users based on the organisation allocation
                $grid->model()->whereIn('id', $OrgAdmins);
            }
        }

        // Get the role ID for 'org' users
        $orgRoleId = AdminRole::where('name', 'org')->value('id');
        $grid->model()->where('user_type', '=', $orgRoleId)->orderBy('created_at', $sortOrder);

        // Define the columns for the grid
        $grid->id('ID')->sortable();
        $grid->addColumn('Admin Name', 'Full Name')->display(function () {
            return $this->first_name . ' ' . $this->last_name;
        })->sortable();
        $grid->sex('Gender')->sortable();
        $grid->column('email_address', 'Email')->display(function () {
            return $this->email;
        });
        $grid->phone_number('Phone Number')->sortable();

        // Adding search filters
        $grid->filter(function ($filter) {
            // Remove the default ID filter
            $filter->disableIdFilter();

            // Add filter for admin name
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');

            // Add filter for email
            $filter->like('email', 'Email');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                        Sort by Established <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="'.url()->current().'?_sort=asc">Ascending</a></li>
                        <li><a href="'.url()->current().'?_sort=desc">Descending</a></li>
                    </ul>
                </div>
            ');
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new User());

        $u = Admin::user();

        // if (!$u->isRole('admin')) {
        //     if ($form->isCreating()) {
        //         admin_error("You are not allowed to create new Agent");
        //         return back();
        //     }
        // }

        $form->text('first_name', 'First Name')->rules('required');
        $form->text('last_name', 'Last Name')->rules('required');
        $form->text('phone_number', 'Phone Number')->rules('required');
        $form->text('email', 'Email');
        $form->select('sex', 'Gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'])->rules('required');

        // Disable user_type selection and set default value to '5' (assuming '5' corresponds to 'org')
        $form->hidden('user_type')->default('5');

        $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $form->hidden('password')->default($password);
        $form->hidden('username')->default($form->phone_number);

        $form->saving(function (Form $form) use ($password) {
            if ($form->isCreating() || $form->isEditing()) {
                // Get the current user being edited
                $currentUser = $form->model();

                // Check if a user with the same phone number already exists
                $existingUser = User::where('phone_number', $form->phone_number)
                                    ->where('id', '!=', $currentUser->id) // Exclude the current user being edited
                                    ->first();

                if ($existingUser) {
                    // User with the same phone number already exists, send message and prevent creation
                    admin_error("A user with the same phone number already exists.");
                    return back();
                }
            }

            // If no user with the same phone number exists, continue saving
            $form->input('password', Hash::make($password));
            $form->input('username', $form->phone_number);


            // // Custom message for registration
            // $platformLink = "https://digisave.m-omulimisa.com/";
            // $message = "Welcome to Digisave VSLA! You have been registered as an organisation administrator. Your login details are: Phone Number: {$form->phone_number}, Password: {$password}. Click here to access the platform: {$platformLink}";
            // $email_info = [
            //     "first_name"=>$form->first_name,
            //     "last_name"=>$form->last_name,
            //     "phone_number"=>$form->phone_number,
            //     "password"=>$password,
            //     "platformLink"=>$platformLink
            // ];
            // // Sending SMS
            // $resp = null;
            // try {
            //     Mail::to($form->email)->send(new SendMail($email_info));
            //     // $resp = Utils::send_sms($form->phone_number, $message);
            // } catch (Exception $e) {
            //     return admin_error('Failed to send email because ' . $e->getMessage());
            // }

            // if ($resp != ) {
            //     return admin_error('Failed to send SMS because ' . $resp);
            // }
        });

        return $form;
    }
}
