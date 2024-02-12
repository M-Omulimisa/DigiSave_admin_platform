<?php

namespace App\Admin\Controllers;

use App\Mail\SendMail;
use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\District;
use App\Models\Parish;
use App\Models\Sacco;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\Village;
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
    protected $title = 'Org Admin';

    protected function grid()
    {
        $grid = new Grid(new User());
    
        $u = Admin::user();
        if (!$u->isRole('admin')) {
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
    
            // Filter users by role 'org'
            
        $orgRoleId = AdminRole::where('name', 'org')->value('id');
        $grid->model()->where('user_type', '=', $orgRoleId);
        }
    
        $grid->id('ID')->sortable();
        $grid->addColumn('Admin Name', 'Full Name')->display(function () {
            return $this->first_name . ' ' . $this->last_name;
        })->sortable();
        $grid->phone_number('Phone Number')->sortable();
        $grid->sex('Gender')->sortable();
        
        $orgRoleId = AdminRole::where('name', 'org')->value('id');
        $grid->model()->where('user_type', '=', $orgRoleId);
    
        return $grid;
    }
    
    

    protected function form()
    {
        $form = new Form(new User());
    
        $u = Admin::user();
    
        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }
    
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
            // Check if a user with the same phone number already exists
            $existingUser = User::where('phone_number', $form->phone_number)->first();
    
            if ($existingUser) {
                // User with the same phone number already exists, send message and prevent creation
                admin_error("A user with the same phone number already exists.");
                return back();
            }
    
            // If no user with the same phone number exists, continue saving
            $form->input('password', Hash::make($password));
            $form->input('username', $form->phone_number);
            
    
            // Custom message for registration
            $platformLink = "https://digisave.m-omulimisa.com/";
            $message = "Welcome to Digisave VSLA! You have been registered as an organisation administrator. Your login details are: Phone Number: {$form->phone_number}, Password: {$password}. Click here to access the platform: {$platformLink}";
            $email_info = [
                "first_name"=>$form->first_name,
                "last_name"=>$form->last_name,
                "phone_number"=>$form->phone_number,
                "password"=>$password,
                "platformLink"=>$platformLink
            ];
            // Sending SMS
            $resp = null;
            try {
                Mail::to($form->email)->send(new SendMail($email_info));
                // $resp = Utils::send_sms($form->phone_number, $message);
            } catch (Exception $e) {
                return admin_error('Failed to send SMS because ' . $e->getMessage());
            }
    
            // if ($resp != ) {
            //     return admin_error('Failed to send SMS because ' . $resp);
            // }
        });
    
        return $form;
    }

}
