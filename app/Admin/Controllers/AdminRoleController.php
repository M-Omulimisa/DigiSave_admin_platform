<?php

namespace App\Admin\Controllers;

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
use Illuminate\Support\Facades\Hash;

class AdminRoleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User Roles';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminRole());

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', 'Role Name')->sortable();
        $grid->column('created_at', 'Created At')->sortable();

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
        $show = new Show(AdminRole::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', 'Role Name');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
{
    $form = new Form(new AdminRole());

    $form->text('name', 'Role Name')->rules('required');
    
    // Add the 'slug' field with a default value of 'admin'
    $form->text('slug', 'Slug')->default('agent')->readonly();

    return $form;
}

}
