<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\VslaOrganisationSacco;
use App\Models\Sacco;
use App\Models\VslaOrganisation;
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
        $grid = new Grid(new VslaOrganisationSacco());

        $u = Admin::user();
        if (!$u->isRole('admin')) {
            /* $grid->model()->where('sacco_id', $u->sacco_id);
  */           if ($u->isRole('org')) {
               // Retrieve the organization IDs assigned to the admin user            
               $orgIds = OrgAllocation::where('user_id', $u->user_id)->pluck('vsla_organisation_id')->toArray();
               // Retrieve the sacco IDs associated with the organization IDs
               $saccoIds = VslaOrganisationSacco::whereIn('vsla_organisation_id', $orgIds)->pluck('sacco_id')->toArray();
               // Filter users based on the retrieved sacco IDs
                $grid->model()->whereIn('vsla_organisation_id', $orgIds);
                $grid->disableCreateButton();
                //dsable delete
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                $grid->disableFilter();
            }
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
        $show = new Show(VslaOrganisationSacco::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('organization.name', 'Organization');
        $show->field('sacco.name', 'Vsla Group');

        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new VslaOrganisationSacco());

    $u = Admin::user();

    if (!$u->isRole('admin')) {
        if ($form->isCreating()) {
            admin_error("You are not allowed to create new Agent");
            return back();
        }
    }

    $form->display('id', 'ID');

    $form->select('vsla_organisation_id', 'Organization')->options(VslaOrganisation::pluck('name', 'id'))->rules('required');

    $form->select('sacco_id', 'Vsla Group')->options(Sacco::pluck('name', 'id'))->rules('required');

    $form->display('created_at', 'Created At');
    $form->display('updated_at', 'Updated At');

    return $form;
}

}
