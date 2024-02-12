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

class AssignAgentsController extends AdminController
{
    protected $title = 'Assign Agents';

    
    protected function grid()
    {
        $grid = new Grid(new AgentAllocation());
        $u = Admin::user();
        if (!$u->isRole('admin')) {
            /* $grid->model()->where('sacco_id', $u->sacco_id);
  */           if ($u->isRole('org')) {
               // Retrieve the organization IDs assigned to the admin user
               $orgIds = Organization::where('agent_id', $u->id)->pluck('id')->toArray();
               // Retrieve the sacco IDs associated with the organization IDs
               $saccoIds = OrganizationAssignment::whereIn('organization_id', $orgIds)->pluck('sacco_id')->toArray();
               // Filter users based on the retrieved sacco IDs
                $grid->model()->whereIn('sacco_id', $saccoIds);
                $grid->disableCreateButton();
                //dsable delete
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                $grid->disableFilter();
            }
        }
    
        $grid->column('id', 'ID')->sortable();
    
        $grid->agent_id('Agent')->display(function ($agentId) {
            $user = User::find($agentId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'N/A';
        })->sortable();
    
        // Display Sacco Name instead of Sacco ID
        $grid->column('sacco_id', 'Vsla Group')->display(function ($saccoId) {
            $sacco = Sacco::find($saccoId);
            return $sacco ? $sacco->name : 'N/A';
        })->sortable();
    
        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();
    
        return $grid;
    }
    
    

    protected function form()
    {
        $form = new Form(new AgentAllocation());
    
        $u = Admin::user();
    
        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to allocate village agent. Contact DigiSave Administartors");
                return back();
            }
        }
    
        $form->display('id', 'ID');
    
        // Select only users whose type is 'agent'
        $agentRole = AdminRole::where('name', 'agent')->first();
        $agentUsers = User::where('user_type', $agentRole->id)
                          ->get(['first_name', 'last_name', 'id'])
                          ->map(function ($user) {
                              return [
                                  'id' => $user->id,
                                  'full_name' => $user->first_name . ' ' . $user->last_name,
                              ];
                          })
                          ->pluck('full_name', 'id');
    
        $form->select('agent_id', 'Agent')->options($agentUsers);
    
        $form->select('sacco_id', 'Vsla Group')->options(Sacco::all()->pluck('name', 'id'));
    
        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');
    
        return $form;
    }
    
}    
