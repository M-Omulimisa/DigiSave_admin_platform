<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\OrgAllocation;
use App\Models\Organization;
use App\Models\Sacco;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class AssignOrganisationAdminController extends AdminController
{
    protected $title = 'Assign Org Admins';

    
    protected function grid()
{
    $grid = new Grid(new OrgAllocation());

    $grid->column('id', 'ID')->sortable();

    // Display Admin Name instead of Admin ID
    $grid->column('admin_id', 'Admin')->display(function ($adminId) {
        $admin = User::find($adminId);
        return $admin ? $admin->first_name . ' ' . $admin->last_name : 'N/A';
    })->sortable();

    // Add Admin Phone Number column
    $grid->column('admin_phone', 'Admin Phone')->display(function () {
        return $this->admin ? $this->admin->phone_number : 'N/A';
    });

    // Display Organization Name instead of Organization ID
    $grid->column('organization_id', 'Organisation')->display(function ($organizationId) {
        $organization = Organization::find($organizationId);
        return $organization ? $organization->name : 'N/A';
    })->sortable();

    $grid->created_at('Created At')->sortable();
    $grid->updated_at('Updated At')->sortable();

    return $grid;
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
    
        // Select only users whose type is 'agent'
        $agentRole = AdminRole::where('name', 'org')->first();
        $agentUsers = User::where('user_type', $agentRole->id)
                          ->get(['first_name', 'last_name', 'id'])
                          ->map(function ($user) {
                              return [
                                  'id' => $user->id,
                                  'full_name' => $user->first_name . ' ' . $user->last_name,
                              ];
                          })
                          ->pluck('full_name', 'id');
    
        $form->select('admin_id', 'Admin')->options($agentUsers);
    
        $form->select('organization_id', 'Organisation')->options(Organization::all()->pluck('name', 'id'));
    
        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');
    
        return $form;
    }
    
}    
