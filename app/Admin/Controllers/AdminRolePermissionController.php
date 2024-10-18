<?php

namespace App\Admin\Controllers;

use App\Models\AdminRolePermission;
use App\Models\AdminRole;
use App\Models\AdminPermission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AdminRolePermissionController extends AdminController
{
    protected $title = 'Role Permissions';

    /**
     * Create the grid view for AdminRolePermission.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminRolePermission());

        // Display basic fields in the grid
        $grid->column('id', __('ID'))->sortable();
        $grid->column('role.name', __('Role'))->sortable();
        $grid->column('permission.name', __('Permission'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('updated_at', __('Updated At'))->sortable();

        // Quick search functionality for role or permission names
        $grid->quickSearch('role.name', 'permission.name');

        return $grid;
    }

    /**
     * Create the detail view for AdminRolePermission.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AdminRolePermission::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('role.name', __('Role'));
        $show->field('permission.name', __('Permission'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Create the form for creating/editing AdminRolePermission.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminRolePermission());

        // Add fields for creating or editing a role-permission relationship
        $form->select('role_id', __('Role'))->options(AdminRole::all()->pluck('name', 'id'))->rules('required');
        $form->select('permission_id', __('Permission'))->options(AdminPermission::all()->pluck('name', 'id'))->rules('required');

        return $form;
    }
}
