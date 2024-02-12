<?php


namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\Sacco;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class OrganizationController extends AdminController
{
    protected $title = 'Organizations';

    protected function grid()
    {
        $grid = new Grid(new Organization());

        $u = Admin::user();
        if (!$u->isRole('admin')) {
            if ($u->isRole('org')) {
            //$grid->model()->where('administrator_id', $u->id);
               // Retrieve the organization IDs assigned to the admin user
               $orgIds = Organization::where('agent_id', $u->id)->pluck('id')->toArray();
               // Retrieve the sacco IDs associated with the organization IDs
               $saccoIds = OrganizationAssignment::whereIn('organization_id', $orgIds)->pluck('sacco_id')->toArray();
               // Filter users based on the retrieved sacco IDs
                $grid->model()->whereIn('id', $orgIds);
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
        }
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('phone_number', 'Phone Number')->sortable();
        $grid->column('unique_code', 'Unique Code')->sortable();
        $grid->column('address', 'Address')->sortable();
        $grid->column('name', 'Name')->sortable();
        $grid->column('agent_id', 'Admin')->display(function ($adminId) {
            $admin = User::find($adminId);
            return $admin ? $admin->first_name . ' ' . $admin->last_name : 'N/A';
        })->sortable();

        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();

        return $grid;
    }
    
    protected function detail($id)
    {
        $show = new Show(Organization::findOrFail($id));
    
        $show->field('id', 'ID');
        $show->field('name', 'Name');
        $show->field('phone_number', 'Phone Number');
        $show->field('address', 'Address');
        $show->field('unique_code', 'Unique Code'); 
    
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');
    
        return $show;
    }
    

    protected function form()
    {
        $form = new Form(new Organization());

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
        $form->text('address', 'Address');
        
        // Retrieve agents and prepare options for a dropdown
        $agentRole = AdminRole::where('name', 'org')->first();
        $agents = User::where('user_type', $agentRole->id)
                      ->get(['first_name', 'last_name', 'id'])
                      ->map(function ($user) {
                          return [
                              'id' => $user->id,
                              'full_name' => $user->first_name . ' ' . $user->last_name,
                          ];
                      })
                      ->pluck('full_name', 'id');
        
        $form->select('agent_id', 'Agent')->options($agents);
        
        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');
        
        return $form;        
}
}