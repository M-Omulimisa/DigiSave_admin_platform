<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AdminRoleController extends AdminController
{
    protected $title = 'Admin Roles';

    protected function grid()
    {
        $grid = new Grid(new AdminRole());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable()->editable();
        $grid->column('slug', __('Slug'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('updated_at', __('Updated At'))->sortable();

        $grid->quickSearch('name', 'slug');
        $grid->disableBatchActions();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(AdminRole::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('slug', __('Slug'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new AdminRole());

        $form->text('name', __('Name'))->rules('required');
        $form->text('slug', __('Slug'))->rules('required');
        $form->datetime('created_at', __('Created At'))->default(date('Y-m-d H:i:s'));
        $form->datetime('updated_at', __('Updated At'))->default(date('Y-m-d H:i:s'));

        return $form;
    }
}
