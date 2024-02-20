<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\OrgAllocation;
use App\Models\OrganizationAssignment;
use App\Models\User;
use App\Models\VslaOrganisation; // Import the VslaOrganisation model
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Show; // Import the Show class

class AssignOrganisationAdminController extends AdminController
{
    protected $title = 'Assign Org Admins';

    protected function grid()
    {
        $grid = new Grid(new OrgAllocation());

        $u = Admin::user();
    
        if (!$u->isRole('admin')) {
            if ($u->isRole('org')) {
                // Retrieve the organization IDs assigned to the admin user
                $orgIds = OrgAllocation::where('user_id', $u->user_id)->pluck('vsla_organisation_id')->toArray();
                // Filter users based on the retrieved sacco IDs
                $grid->model()->whereIn('vsla_organisation_id', $orgIds);
                $grid->disableCreateButton();
                // Disable delete
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                // Filter by user_type
                $grid->model()->where(function ($query) {
                    $query->whereNull('user_type')->orWhereNotIn('user_type', ['Admin', '5']);
                });
    
                $grid->disableFilter();
            }
        } 
    
        $grid->column('id', 'ID')->sortable();
    
        $grid->column('admin', 'Admin')->display(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        })->sortable();
    
        $grid->column('admin_email', 'Admin Email')->display(function () {
            return $this->admin ? $this->admin->email : 'N/A';
        });
    
        $grid->column('admin_phone', 'Admin Phone')->display(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
    
        $grid->column('organization', 'VSLA Organisation')->display(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        })->sortable();
    
        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();
    
        return $grid;
    }

    // Add the detail method
    protected function detail($id)
    {
        $show = new Show(OrgAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('admin', 'Admin')->as(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        });
        $show->field('admin_phone', 'Admin Phone')->as(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
        $show->field('organization', 'VSLA Organisation')->as(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        });
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new OrgAllocation());

    $u = Admin::user();

    if (!$u->isRole('admin')) {
        if ($form->isCreating()) {
            admin_error("You are not allowed to create a new Agent Allocation");
            return back();
        }
    }

    $form->display('id', 'ID');

    // Select only users whose type is 'org' (assuming this role corresponds to VSLA organization admins)
    $orgRole = AdminRole::where('name', 'org')->first();
    $orgUsers = User::where('user_type', $orgRole->id)
        ->get(['first_name', 'last_name', 'id'])
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ];
        })
        ->pluck('full_name', 'id');

    $form->select('user_id', 'Admin')->options($orgUsers); // Change 'admin' to 'user_id'

    $form->select('vsla_organisation_id', 'VSLA Organisation')->options(VslaOrganisation::all()->pluck('name', 'id'));

    $form->display('created_at', 'Created At');
    $form->display('updated_at', 'Updated At');

    // Add custom validation to ensure uniqueness of admin within organization
    $form->saving(function (Form $form) {
        $existingAllocation = OrgAllocation::where('user_id', $form->user_id)
            ->where('vsla_organisation_id', $form->vsla_organisation_id)
            ->exists();

        if ($existingAllocation) {
            admin_error("This admin is already assigned to the selected organization.");
            return back();
        }
    });

    return $form;
}

    
}
