<?php

namespace App\Admin\Controllers;

use App\Models\AdminRoleUser;
use App\Models\AdminRole;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AdminRoleUserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Admin Role User Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminRoleUser());

        // Default sort order
        $grid->model()->orderBy('created_at', 'desc');

        // Display Role and User details
        $grid->column('role.name', __('Role'))->sortable();
        $grid->column('user_id', __('User'))->display(function ($userId) {
            $user = User::find($userId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown User';
        })->sortable();

        $grid->column('created_at', __('Assigned At'))->sortable()->display(function ($createdAt) {
            return date('d M Y H:i:s', strtotime($createdAt));
        });

        $grid->disableBatchActions();
        $grid->quickSearch('role.name', 'user.first_name', 'user.last_name')->placeholder('Search by Role or User');

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
        $show = new Show(AdminRoleUser::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('role.name', __('Role'));

        $show->field('user_id', __('User'))->as(function ($userId) {
            $user = User::find($userId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown User';
        });

        $show->field('created_at', __('Assigned At'))->as(function ($createdAt) {
            return date('d M Y H:i:s', strtotime($createdAt));
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminRoleUser());

        // Fetch roles and users for the dropdown
        $form->select('role_id', __('Role'))
            ->options(AdminRole::all()->pluck('name', 'id'))
            ->rules('required');

        $form->select('user_id', __('User'))
            ->options(User::all()->pluck('first_name', 'id')->map(function ($firstName, $id) {
                $user = User::find($id);
                return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown User';
            }))
            ->rules('required');

        $form->saving(function (Form $form) {
            // Perform any necessary logic before saving the form
        });

        $form->saved(function (Form $form) {
            admin_success('Success', 'Role assigned to user successfully');
        });

        return $form;
    }
}
