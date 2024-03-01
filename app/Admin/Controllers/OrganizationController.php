<?php


namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\OrgAllocation;
use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\Sacco;
use App\Models\User;
use App\Models\VslaOrganisation;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class OrganizationController extends AdminController
{
    protected $title = 'VSLA Organizations';

    protected function grid()
    {
        $grid = new Grid(new VslaOrganisation());
        
        $admin = Admin::user();
        $adminId = $admin->id;
        if (!$admin->isRole('admin')) {
    
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                // die(print_r($orgId));
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();

                // Extracting Sacco IDs from the assignments
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                // die(print_r($saccoIds));

                $grid->model()->where('id', $orgId);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                $grid->disableFilter();
            }
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', 'Organisation Name')->sortable();
        $grid->column('phone_number', 'Phone Number')->sortable();
        $grid->column('email', 'Email Address')->sortable();
        $grid->column('unique_code', 'Unique Code')->sortable();

        // $grid->created_at('Created At')->sortable();
        // $grid->updated_at('Updated At')->sortable();

        return $grid;
    }
    
    protected function detail($id)
    {
        $show = new Show(VslaOrganisation::findOrFail($id));
    
        $show->field('id', 'ID');
        $show->field('name', 'Name');
        $show->field('phone_number', 'Phone Number');
        $show->field('email', 'Address');
        $show->field('unique_code', 'Unique Code'); 
    
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');
    
        return $show;
    }
    

    protected function form()
    {
        $form = new Form(new VslaOrganisation());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->display('id', 'ID');
        $form->text('name', 'Name')->rules('required');
        $form->text('phone_number', 'Phone Number')->rules('required');
        $form->text('unique_code', 'Unique Code')->readonly();
        $form->text('email', 'Email Address');
        
        // // Retrieve agents and prepare options for a dropdown
        // $agentRole = AdminRole::where('name', 'org')->first();
        // $agents = User::where('user_type', $agentRole->id)
        //               ->get(['first_name', 'last_name', 'id'])
        //               ->map(function ($user) {
        //                   return [
        //                       'id' => $user->id,
        //                       'full_name' => $user->first_name . ' ' . $user->last_name,
        //                   ];
        //               })
        //               ->pluck('full_name', 'id');
        
        // $form->select('agent_id', 'Agent')->options($agents);
        
        // $form->display('created_at', 'Created At');
        // $form->display('updated_at', 'Updated At');
        
        return $form;        
}
}