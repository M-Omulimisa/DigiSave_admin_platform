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
use Carbon\Carbon;

class AgentGroupAllocationController extends AdminController
{
    protected $title = 'Agent Group Allocations';

    protected function grid()
    {
        $grid = new Grid(new AgentGroupAllocation());

        // Add JavaScript for modal functionality first
        Admin::script($this->modalScript());

        $grid->model()->select('agent_id', DB::raw('COUNT(*) as group_count'),
                             DB::raw('MAX(allocated_at) as latest_allocation'))
            ->whereNotNull('agent_id')
            ->groupBy('agent_id');

        $grid->column('agent.first_name', 'First Name')->sortable();
        $grid->column('agent.last_name', 'Last Name')->sortable();
        $grid->column('agent.phone_number', 'Phone Number');

        $grid->column('group_count', 'Assigned Groups')->sortable();

        $grid->column('latest_allocation', 'Latest Allocation')->display(function ($date) {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        })->sortable();

        $grid->column('Groups')->display(function () {
            // Get agent details
            $agent = User::find($this->agent_id);

            // Get all allocations for this agent
            $allocations = AgentGroupAllocation::with('sacco')
                ->where('agent_id', $this->agent_id)
                ->get();

            // Build table HTML
            $tableHtml = "<table class='table table-bordered' style='margin-bottom: 0;'><thead><tr>";
            $tableHtml .= "<th>Group Name</th><th>District</th><th>Action</th></tr></thead><tbody>";

            foreach ($allocations as $allocation) {
                $tableHtml .= "<tr>";
                $tableHtml .= "<td>{$allocation->sacco->name}</td>";
                $tableHtml .= "<td>{$allocation->sacco->district}</td>";
                $tableHtml .= "<td><button class='btn btn-xs btn-danger deassign-group' data-id='{$allocation->id}'>Deassign</button></td>";
                $tableHtml .= "</tr>";
            }

            $tableHtml .= "</tbody></table>";

            // Create a data attribute to store the HTML
            return "<div class='group-allocation-btn'
                       data-agent-name='{$agent->first_name} {$agent->last_name}'
                       data-groups-html='" . htmlspecialchars($tableHtml, ENT_QUOTES) . "'>
                    <a href='javascript:void(0);' class='btn btn-sm btn-info show-groups'>View Groups</a>
                   </div>";
        });

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
        });

        $grid->disableBatchActions();

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
        $allocatedSaccoIds = AgentGroupAllocation::pluck('sacco_id')->toArray();
        $saccos = Sacco::whereNotIn('id', $allocatedSaccoIds)
            ->get()
            ->map(function ($sacco) {
                return [
                    'id' => $sacco->id,
                    'name' => $sacco->name . ' (' . ($sacco->district) . ')'
                ];
            })
            ->pluck('name', 'id');

        $form->select('agent_id', 'Agent')
            ->options($agents)
            ->rules('required');

        $form->listbox('sacco_id', 'Groups')
            ->options($saccos)
            ->rules('required')
            ->help('You can select multiple groups to allocate to this agent');

        $form->ignore(['sacco_id']);

        $form->saving(function (Form $form) {
            $agentId = $form->agent_id;
            $saccoIds = array_filter((array)request()->input('sacco_id', []));

            if (empty($saccoIds)) {
                return back()->withInput()->withErrors(['sacco_id' => 'Please select at least one group to allocate.']);
            }

            try {
                DB::beginTransaction();

                foreach ($saccoIds as $saccoId) {
                    // Check for existing allocation
                    $existingAllocation = AgentGroupAllocation::where('sacco_id', $saccoId)
                        ->first();

                    if ($existingAllocation && $form->isCreating()) {
                        $sacco = Sacco::find($saccoId);
                        throw new \Exception("Group '{$sacco->name}' is already allocated to another agent.");
                    }

                    // Create new allocation
                    if (isset($data['user_id']) && !empty($data['user_id'])) {
                        $allocatedBy = $data['user_id'];
                    } else {
                        $admin = Admin::first();

                        if ($admin) {
                            $allocatedBy = $admin->id;
                        } else {
                            $anyUser = User::first();
                            $allocatedBy = $anyUser ? $anyUser->id : 1;
                        }
                    }

                    AgentGroupAllocation::create([
                        'agent_id' => $data['user_id'] ?? null,
                        'sacco_id' => $newGroup->id,
                        'allocated_at' => now(),
                        'allocated_by' => $allocatedBy
                    ]);
                }

                DB::commit();
                admin_success('Success', 'Groups allocated successfully.');
                return redirect(admin_url('agent-group-allocations'));

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => $e->getMessage()]);
            }
        });

        return $form;
    }

    protected function modalScript()
    {
        return <<<EOT
        $(document).ready(function () {
            // Add modal HTML if it doesn't exist
            if ($('#group-modal').length === 0) {
                $('body').append(`
                    <div class="modal fade" id="group-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">Assigned Groups</h4>
                                </div>
                                <div class="modal-body"></div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Bind click event to View Groups buttons
            $(document).on('click', '.show-groups', function() {
                var container = $(this).closest('.group-allocation-btn');
                var agentName = container.data('agent-name');
                var groupsHtml = container.data('groups-html');

                $('#group-modal .modal-title').text(agentName + "'s Groups");
                $('#group-modal .modal-body').html(groupsHtml);
                $('#group-modal').modal('show');
            });

            // Bind click event to Deassign buttons
            $(document).on('click', '.deassign-group', function() {
                var allocationId = $(this).data('id');
                if(confirm('Are you sure you want to deassign this group?')) {
                    $.ajax({
                        method: 'DELETE',
                        url: 'agent-group-allocations/' + allocationId,
                        data: {
                            _token: LA.token
                        },
                        success: function(response) {
                            toastr.success('Group deassigned successfully');
                            $.pjax.reload('#pjax-container');
                            $('#group-modal').modal('hide');
                        },
                        error: function(response) {
                            toastr.error('Error deassigning group');
                        }
                    });
                }
            });
        });
EOT;
    }

    protected function detail($id)
    {
        $show = new Show(AgentGroupAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('agent.first_name', 'First Name');
        $show->field('agent.last_name', 'Last Name');
        $show->field('agent.phone_number', 'Phone Number');
        $show->field('sacco.name', 'Group Name');
        $show->field('sacco.district', 'District');
        $show->field('allocated_at', 'Allocation Date');
        $show->field('allocator.name', 'Allocated By');

        return $show;
    }
}
