<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\AdminRole;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use App\Models\Utils;
use App\Mail\ResetPasswordMail;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;

class MemberAdminController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Admins';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        // Restrict access to only admin users
        if (!Admin::user()->isRole('admin')) {
            admin_warning('Warning', 'You are not authorized to view this page.');
            return redirect(admin_url('/'));
        }

        // Display relevant columns
        $grid->column('id', __('ID'))->sortable();
        $grid->column('first_name', __('First Name'))->sortable();
        $grid->column('last_name', __('Last Name'))->sortable();
        $grid->column('phone_number', __('Phone Number'))->sortable();
        $grid->column('email', __('Email Address'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();

        // Add filtering capabilities
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('email', 'Email');
            $filter->like('phone_number', 'Phone Number');
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('first_name', __('First Name'));
        $show->field('last_name', __('Last Name'));
        $show->field('email', __('Email'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
{
    $form = new Form(new User());

    // Standard form fields
    $form->text('first_name', __('First Name'))->rules('required');
    $form->text('last_name', __('Last Name'))->rules('required');
    $form->text('phone_number', __('Phone Number'))->rules('required');
    $form->email('email', __('Email'))->rules('required|email|unique:users,email,{{id}}');

    // Password will be generated automatically
    $form->hidden('password', __('Password'));

    // Hidden fields for user type and sacco_join_status
    $form->hidden('user_type')->default('Admin');
    $form->hidden('sacco_join_status')->default('approved'); // Set the status to 'approved'

    // Generate password and set username in the `saving` closure
    $form->saving(function (Form $form) {
        // Generate a unique 6-digit password
        $plainPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Hash the password and store it in the database
        $form->password = Hash::make($plainPassword);

        // Temporarily store the plain password for sending via SMS and email after saving
        $form->plain_password = $plainPassword;

        // Set the username as the phone number
        $form->model()->username = $form->phone_number;
    });

    // Send SMS and email after saving the form
    $form->saved(function (Form $form) {
        // Get the required fields
        $user = $form->model();
        $plainPassword = $form->plain_password;

        // Send SMS
        $smsMessage = "Your admin account has been created. Use this password to log in: {$plainPassword}";
        Utils::send_sms($user->phone_number, $smsMessage);

        // Prepare email data
        $emailData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->email,
            'password' => $plainPassword,
            'platformLink' => 'https://digisave.m-omulimisa.com/', // Update with actual link
            'email' => $user->email,
            'org' => 'DigiSave VSLA', // Add any organization details if needed
        ];

        // Send email
        Mail::to($user->email)->send(new SendMail($emailData));

        admin_success('Success', 'Admin account created successfully, and password sent via SMS and Email.');
    });

    return $form;
}


}
