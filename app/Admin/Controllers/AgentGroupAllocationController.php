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

        $grid->column('id', 'ID')->sortable();
        $grid->column('agent.first_name', 'First Name')->sortable();
        $grid->column('agent.last_name', 'Last Name')->sortable();
        $grid->column('agent.phone_number', 'Phone Number');
        $grid->column('sacco.name', 'Group Name')->sortable();
        $grid->column('sacco.district.name', 'District')->sortable();

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

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $agents = User::where('user_type', '4')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name
                    ];
                })->pluck('name', 'id');
            $filter->equal('agent_id', 'Agent')->select($agents);

            $districts = DB::table('districts')->pluck('name', 'id');
            $filter->equal('sacco.district_id', 'District')->select($districts);

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
        $agents = User::where('user_type', '4')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name
                ];
            })->pluck('name', 'id');

        // Get available Groups (Saccos)
        $allocatedSaccoIds = AgentGroupAllocation::where('status', 'active')->pluck('sacco_id')->toArray();
        $saccos = Sacco::whereNotIn('id', $allocatedSaccoIds)
            ->orWhereIn('id', $allocatedSaccoIds)
            ->get()
            ->map(function ($sacco) {
                return [
                    'id' => $sacco->id,
                    'name' => $sacco->name . ' (' . ($sacco->district->name ?? 'Unknown District') . ')'
                ];
            })
            ->pluck('name', 'id');

        $form->select('agent_id', 'Agent')
            ->options($agents)
            ->rules('required');

        $form->listbox('sacco_id', 'Groups')  // Changed to listbox for better multiple selection handling
            ->options($saccos)
            ->rules('required')
            ->help('You can select multiple groups to allocate to this agent');

        $form->radio('status', 'Status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ])
            ->default('active');

        $form->ignore(['sacco_id']);  // Ignore the field from default form processing

        $$form->saving(function (Form $form) {
            $agentId = $form->agent_id;
            $saccoIds = array_filter((array)request()->input('sacco_id', []));
            $status = $form->status;

            if (empty($saccoIds)) {
                return back()->withInput()->withErrors(['sacco_id' => 'Please select at least one group to allocate.']);
            }

            try {
                DB::beginTransaction();

                // Get agent name for the success message
                $agent = User::find($agentId);
                $allocatedGroups = [];

                foreach ($saccoIds as $saccoId) {
                    // Check for existing active allocation
                    $existingAllocation = AgentGroupAllocation::where('sacco_id', $saccoId)
                        ->where('status', 'active')
                        ->first();

                    if ($existingAllocation && $form->isCreating()) {
                        $sacco = Sacco::find($saccoId);
                        throw new \Exception("Group '{$sacco->name}' is already allocated to another agent.");
                    }

                    // Store group name for success message
                    $sacco = Sacco::find($saccoId);
                    $allocatedGroups[] = $sacco->name;

                    // Create new allocation
                    AgentGroupAllocation::create([
                        'agent_id' => $agentId,
                        'sacco_id' => $saccoId,
                        'status' => $status,
                        'allocated_at' => now(),
                        'allocated_by' => Admin::user()->id
                    ]);
                }

                DB::commit();

                // Create detailed success message
                $groupNames = implode(', ', $allocatedGroups);
                $agentName = $agent->first_name . ' ' . $agent->last_name;
                admin_success(
                    'Groups Allocated Successfully',
                    "Successfully allocated the following groups to {$agentName}:<br>" .
                    "<ul><li>" . implode('</li><li>', $allocatedGroups) . "</li></ul>"
                );

                // Store success message in session for grid view
                session()->flash('success', [
                    'title' => 'Groups Allocated Successfully',
                    'message' => "Groups have been allocated to {$agentName}"
                ]);

                return redirect(admin_url('agent-group-allocations'));

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => $e->getMessage()]);
            }
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
