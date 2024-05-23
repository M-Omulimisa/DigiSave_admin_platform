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

        $admin = Admin::user();
        $adminId = $admin->id;
        if (!$admin->isRole('admin')) {

            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                // die(print_r($orgId));
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();

                // Extracting Sacco IDs from the assignments
                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgId)->pluck('user_id')->toArray();
                // die(print_r($saccoIds));

                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->where('vsla_organisation_id', $orgId);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });            }
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
