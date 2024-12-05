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

        // Agent information with relationship
        $grid->column('agent.first_name', 'First Name')->sortable();
        $grid->column('agent.last_name', 'Last Name')->sortable();
        $grid->column('agent.phone_number', 'Phone Number');

        // Group information with nested relationships
        $grid->column('sacco.name', 'Group Name')->sortable();
        $grid->column('sacco.district.name', 'District')->sortable();

        // Count groups per agent
        $grid->column('Groups Count')->display(function () {
            return AgentGroupAllocation::where('agent_id', $this->agent_id)
                ->where('status', 'active')
                ->count();
        });

        $grid->column('status', 'Status')->display(function ($status) {
            $color = $status === 'active' ? 'success' : 'danger';
            return "<span class='label label-$color'>$status</span>";
        });

        $grid->column('allocated_at', 'Allocated Date')->sortable();

        // Filter options
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Agent filter
            $agents = User::whereHas('roles', function($q) {
                $q->where('name', 'agent');
            })->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name
                ];
            })->pluck('name', 'id');
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

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new AgentGroupAllocation());

        // Get agents
        $agents = User::whereHas('roles', function($q) {
            $q->where('name', 'agent');
        })->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name
            ];
        })->pluck('name', 'id');

        // Get available Groups (Saccos)
        $allocatedSaccoIds = AgentGroupAllocation::where('status', 'active')->pluck('sacco_id')->toArray();
        $saccos = Sacco::whereNotIn('id', $allocatedSaccoIds)
            ->orWhereIn('id', $allocatedSaccoIds) // Include already allocated groups
            ->get()
            ->map(function ($sacco) {
                return [
                    'id' => $sacco->id,
                    'name' => $sacco->name . ' (' . ($sacco->district->name ?? 'Unknown District') . ')'
                ];
            })
            ->pluck('name', 'id');

        // Add fields
        $form->select('agent_id', 'Agent')
            ->options($agents)
            ->rules('required');

        // Multiple group selection
        $form->multipleSelect('sacco_id', 'Groups')
            ->options($saccos)
            ->rules('required')
            ->help('You can select multiple groups to allocate to this agent');

        $form->radio('status', 'Status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ])
            ->default('active');

        // Handle saving multiple allocations
        $form->saving(function (Form $form) {
            // Convert single sacco_id to array if necessary
            $saccoIds = is_array($form->sacco_id) ? $form->sacco_id : [$form->sacco_id];

            // Store original form data
            $agentId = $form->agent_id;
            $status = $form->status;

            // Remove sacco_id as we'll handle it manually
            unset($form->input['sacco_id']);

            // Create allocations for each selected group
            foreach ($saccoIds as $saccoId) {
                // Check if allocation already exists
                $existingAllocation = AgentGroupAllocation::where('sacco_id', $saccoId)
                    ->where('status', 'active')
                    ->first();

                if ($existingAllocation && $form->isCreating()) {
                    $sacco = Sacco::find($saccoId);
                    $error = new \Exception("Group '{$sacco->name}' is already allocated to another agent.");
                    return back()->withInput()->withErrors(['error' => $error->getMessage()]);
                }

                // Create new allocation
                if ($form->isCreating()) {
                    AgentGroupAllocation::create([
                        'agent_id' => $agentId,
                        'sacco_id' => $saccoId,
                        'status' => $status,
                        'allocated_at' => now(),
                        'allocated_by' => Admin::user()->id
                    ]);
                }
            }

            // Prevent the original form submission
            return false;
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(AgentGroupAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('agent.first_name', 'First Name');
        $show->field('agent.last_name', 'Last Name');
        $show->field('agent.phone_number', 'Phone Number');
        $show->field('sacco.name', 'Group Name');
        $show->field('sacco.district.name', 'District');
        $show->field('status', 'Status');
        $show->field('allocated_at', 'Allocated Date');
        $show->field('allocator.name', 'Allocated By');

        return $show;
    }
}
