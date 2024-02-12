<?php

namespace App\Admin\Controllers;

use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class OrganizationAllocationController extends AdminController
{
    protected $title = 'Organization Assignments';

    protected function grid()
    {
        $grid = new Grid(new OrganizationAssignment());

        $u = Admin::user();
        if (!$u->isRole('admin')) {
            // $grid->model()->where('administrator_id', $u->id);
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('organization.name', 'Organization')->sortable();
        $grid->column('sacco.name', 'Vsla Group')->sortable();

        // $grid->created_at('Created At')->sortable();
        // $grid->updated_at('Updated At')->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(OrganizationAssignment::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('organization.name', 'Organization');
        $show->field('sacco.name', 'Vsla Group');

        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new OrganizationAssignment());

    $u = Admin::user();

    if (!$u->isRole('admin')) {
        if ($form->isCreating()) {
            admin_error("You are not allowed to create new Agent");
            return back();
        }
    }

    $form->display('id', 'ID');

    $form->select('organization_id', 'Organization')->options(Organization::pluck('name', 'id'))->rules('required');

    $form->select('sacco_id', 'Vsla Group')->options(Sacco::pluck('name', 'id'))->rules('required');

    $form->display('created_at', 'Created At');
    $form->display('updated_at', 'Updated At');

    return $form;
}

}
