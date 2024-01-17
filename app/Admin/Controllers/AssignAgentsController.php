<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\Organization;
use App\Models\Sacco;
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
    
        // ... (existing code)
    
        $grid->column('id', 'ID')->sortable();

        $grid->agent()->full_name('Agent')->sortable();
        // Display Sacco Name instead of Sacco ID
        $grid->column('sacco_id', 'Sacco')->display(function ($saccoId) {
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
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->display('id', 'ID');

        $form->select('agent_id', 'Agent')->options(Agent::all()->pluck('full_name', 'id'));
        $form->select('sacco_id', 'Sacco')->options(Sacco::all()->pluck('name', 'id'));

        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');

        return $form;
    }

}
