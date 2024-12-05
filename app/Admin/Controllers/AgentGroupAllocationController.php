<?php

namespace App\Admin\Controllers;

use App\Models\AgentGroupAllocation;
use App\Models\AdminRole;
use App\Models\Sacco;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class AgentGroupAllocationController extends AdminController
{
    protected $title = 'Agent Group Allocations';

    protected function grid()
    {
        $grid = new Grid(new AgentGroupAllocation());

        // Add columns
        $grid->column('id', 'ID')->sortable();

        // Agent information
        $grid->column('agent.full_name', 'Agent Name')->sortable();
        $grid->column('agent.phone_number', 'Agent Phone');

        // Group information
        $grid->column('sacco.name', 'Group Name')->sortable();
        $grid->column('sacco.district.name', 'District')->sortable();
        $grid->column('sacco.subcounty.sub_county', 'Subcounty');

        // Allocation details
        $grid->column('status', 'Status')->display(function ($status) {
            $color = $status === 'active' ? 'success' : 'danger';
            return "<span class='label label-$color'>$status</span>";
        });
        $grid->column('allocated_at', 'Allocated Date')->sortable();
        $grid->column('allocator.name', 'Allocated By');

        // Filter options
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Agent filter
            $agents = User::whereHas('roles', function($q) {
                $q->where('name', 'agent');
            })->pluck('first_name', 'id');
            $filter->equal('agent_id', 'Agent')->select($agents);

            // District filter
            $districts = DB::table('districts')->pluck('name', 'id');
            $filter->equal('sacco.district_id', 'District')->select($districts);

            // Status filter
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ]);
        });

        // Add row actions
        $grid->actions(function ($actions) {
            $actions->disableView();
            if (!Admin::user()->isRole('admin')) {
                $actions->disableDelete();
                $actions->disableEdit();
            }
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new AgentGroupAllocation());

        // Get agents
        $agents = User::whereHas('roles', function($q) {
            $q->where('name', 'agent');
        })->get()->pluck('full_name', 'id');

        // Get unallocated Groups (Saccos)
        $allocatedSaccoIds = AgentGroupAllocation::where('status', 'active')->pluck('sacco_id')->toArray();
        $saccos = Sacco::whereNotIn('id', $allocatedSaccoIds)->pluck('name', 'id');

        // Add fields
        $form->select('agent_id', 'Agent')
            ->options($agents)
            ->rules('required');

        $form->select('sacco_id', 'Group')
            ->options($saccos)
            ->rules('required');

        $form->radio('status', 'Status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ])
            ->default('active');

        // Handle saving
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                // Check if Group is already allocated
                $existingAllocation = AgentGroupAllocation::where('sacco_id', $form->sacco_id)
                    ->where('status', 'active')
                    ->first();

                if ($existingAllocation) {
                    $error = new \Exception('This group is already allocated to another agent.');
                    return back()->withInput()->withErrors(['error' => $error->getMessage()]);
                }
            }
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(AgentGroupAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('agent.full_name', 'Agent Name');
        $show->field('agent.phone_number', 'Agent Phone');
        $show->field('sacco.name', 'Group Name');
        $show->field('sacco.district.name', 'District');
        $show->field('sacco.subcounty.sub_county', 'Subcounty');
        $show->field('status', 'Status');
        $show->field('allocated_at', 'Allocated Date');
        $show->field('allocator.name', 'Allocated By');

        return $show;
    }
}
