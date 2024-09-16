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

        // Join the `admin_role_users` and `admin_roles` tables to get only users with roles
        $grid->model()->whereHas('roles', function ($query) {
            $query->whereIn('slug', ['admin', 'org', 'agent']);
        });

        // Display relevant columns
        $grid->column('id', __('ID'))->sortable();
        $grid->column('first_name', __('First Name'))->sortable();
        $grid->column('last_name', __('Last Name'))->sortable();
        $grid->column('phone_number', __('Phone Number'))->sortable();
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

        $form->text('first_name', __('First Name'))->rules('required');
        $form->text('last_name', __('Last Name'))->rules('required');
        $form->text('phone_number', __('Phone Number'))->rules('required');
        $form->email('email', __('Email'))->rules('required|email|unique:users,email,{{id}}');

        // Password will be generated automatically, so remove the field for manual input
        $form->hidden('password', __('Password'));

        // Additional fields for admin users
        $form->hidden('user_type')->default('Admin');

        $form->saving(function (Form $form) {
            // Generate a unique 6-digit password
            $password = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Hash the password
            $form->password = Hash::make($password);

            // You can log the password here if you want to provide it to the admin/user
            // Log::info('Generated password: ' . $password);
        });

        return $form;
    }
}
