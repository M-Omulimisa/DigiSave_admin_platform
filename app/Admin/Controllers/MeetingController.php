<?php

namespace App\Admin\Controllers;

use App\Models\Cycle;
use App\Models\Meeting;
use App\Models\Sacco;
use App\Models\User;
use App\Models\OrgAllocation;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class MeetingController extends AdminController
{
    protected $title = 'Meetings';

    protected function grid()
    {
        $grid = new Grid(new Meeting());

        $grid->model()->whereHas('sacco', function ($query) {
            $query->whereNotNull('name')
                  ->where('name', '!=', '')
                  ->whereNotIn('status', ['deleted', 'inactive']);
        });

        $u = Auth::user();
        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Process date range filters
        if ($startDate = request('start_date')) {
            $grid->model()->whereDate('date', '>=', $startDate);
        }

        if ($endDate = request('end_date')) {
            $grid->model()->whereDate('date', '<=', $endDate);
        }

        // Process district filter
        if ($district = request('district')) {
            $grid->model()->whereHas('sacco', function ($query) use ($district) {
                $query->where('district', $district);
            });
        }

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $adminRegion = trim($orgAllocation->region);

                if (empty($adminRegion)) {
                    // If no region is specified, get all SACCOs for the organization
                    $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)
                        ->pluck('sacco_id')
                        ->toArray();
                } else {
                    // Get only SACCOs in the admin's region
                    $saccoIds = VslaOrganisationSacco::join('saccos', 'vsla_organisation_sacco.sacco_id', '=', 'saccos.id')
                        ->where('vsla_organisation_sacco.vsla_organisation_id', $orgId)
                        ->whereRaw('LOWER(saccos.district) = ?', [strtolower($adminRegion)])
                        ->pluck('sacco_id')
                        ->toArray();
                }

                $grid->model()
                    ->whereIn('sacco_id', $saccoIds)
                    ->whereHas('sacco', function ($query) {
                        $query->whereNotIn('status', ['deleted', 'inactive']);
                    })
                    ->orderBy('created_at', $sortOrder);

                $grid->disableCreateButton();
            }
        } else {
            $grid->model()
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->orderBy('created_at', $sortOrder);
        }

        $grid->model()
             ->whereHas('cycle', function ($query) {
                $query->where('status', 'active');
             })
             ->orderBy('created_at', $sortOrder);

        // Quick search
        $grid->disableBatchActions();
        $grid->quickSearch(function ($model, $query) {
            $model->whereHas('sacco', function ($query2) use ($query) {
                $query2->where('name', 'like', "%{$query}%");
            });
        })->placeholder('Search by group name');

        // Filter tools
        $grid->tools(function ($tools) {
            // Get districts for dropdown
            $districts = Sacco::whereNotIn('status', ['deleted', 'inactive'])
                ->whereNotNull('district')
                ->distinct()
                ->pluck('district')
                ->sort()
                ->values();

            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px;">
                    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                        Filter by District <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="' . url()->current() . '">All Districts</a></li>
                        ' . $districts->map(function ($district) {
                            return '<li><a href="' . url()->current() . '?district=' . urlencode($district) . '">'
                                . ucwords(strtolower($district)) . '</a></li>';
                        })->implode('') . '
                    </ul>
                </div>

                <div class="btn-group pull-right" style="margin-right: 10px;">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#dateFilterModal">
                        Filter by Date Range
                    </button>
                </div>

                <a href="' . url()->current() . '" class="btn btn-sm btn-warning pull-right" style="margin-right: 10px;">
                    <i class="fa fa-refresh"></i> Reset Filters
                </a>

                <div class="modal fade" id="dateFilterModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Filter by Date Range</h4>
                            </div>
                            <form action="' . url()->current() . '" method="get">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Start Date:</label>
                                        <input type="date" name="start_date" class="form-control" value="' . request('start_date') . '">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date:</label>
                                        <input type="date" name="end_date" class="form-control" value="' . request('end_date') . '">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            ');
        });

        // Grid columns
        $grid->column('sacco.name', __('Group Name'))->sortable();
        $grid->column('sacco.district', __('District'))->sortable();
        $grid->column('cycle_id', __('Cycle'))->display(function ($cycleId) {
            $cycle = Cycle::find($cycleId);
            return $cycle ? $cycle->name : 'Unknown';
        });
        $grid->column('name', __('Meeting'))->display(function ($name) {
            $parts = explode(' ', $name);
            if (count($parts) >= 2) {
                return $parts[0] . ' ' . $parts[1];
            }
            return $name;
        })->sortable();
        $grid->column('date', __('Date'))->sortable();
        $grid->column('chairperson_name', __('Chairperson Name'))
            ->sortable()
            ->display(function () {
                $user = User::where('sacco_id', $this->sacco_id)
                    ->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                    ->first();
                return $user ? ucwords(strtolower($user->name)) : '';
            });

        // Your existing members and minutes columns...

        // Filter configuration
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('sacco.name', 'Group Name');
            $filter->like('sacco.district', 'District');
            $filter->between('date', 'Meeting Date')->date();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Meeting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('date', __('Date'));
        $show->field('location', __('Location'));
        $show->field('sacco.name', __('Group Name'));
        $show->field('administrator.name', __('Administrator Name'));
        $show->field('members', __('Attendance'))->as(function ($members) {
            $memberIds = json_decode($members, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($memberIds) && !empty($memberIds)) {
                $memberNames = User::whereIn('id', $memberIds)->get()->pluck('name')->toArray();
                $formattedMembers = '<div class="card-deck">';
                foreach ($memberNames as $name) {
                    $formattedMembers .= '<div class="card text-white bg-info mb-3" style="max-width: 18rem;">';
                    $formattedMembers .= '<div class="card-body"><h5 class="card-title">' . $name . '</h5></div>';
                    $formattedMembers .= '</div>';
                }
                $formattedMembers .= '</div>';
                return $formattedMembers;
            }
            return 'No attendance recorded';
        });
        $show->field('minutes', __('Minutes'))->as(function ($minutes) {
            $minutesData = json_decode($minutes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $formattedMinutes = '<div class="row">';
                foreach ($minutesData as $section => $items) {
                    $formattedMinutes .= '<div class="col-md-6"><div class="card"><div class="card-body">';
                    $formattedMinutes .= '<h5 class="card-title">' . ucfirst(str_replace('_', ' ', $section)) . ':</h5><ul class="list-group list-group-flush">';
                    foreach ($items as $item) {
                        $formattedMinutes .= '<li class="list-group-item">' . $item['title'] . ': ' . $item['value'] . '</li>';
                    }
                    $formattedMinutes .= '</ul></div></div></div>';
                }
                $formattedMinutes .= '</div>';
                return $formattedMinutes;
            }
            return $minutes; // return as is if JSON decoding fails
        });

        $show->field('attendance', __('Attendance'));
        $show->field('cycle_id', __('Cycle ID'));

        return $show;
    }

    protected function form()
    {
        $u = Auth::user();

        $form = new Form(new Meeting());

        $sacco_id = $u->sacco_id;

        $activeCycle = \App\Models\Cycle::where('sacco_id', $sacco_id)->where('status', 'Active')->first();

        if ($activeCycle) {
            $form->hidden('cycle_id')->default($activeCycle->id);
        } else {
            $form->hidden('cycle_id')->default(0);
        }

        $form->hidden('sacco_id')->default($sacco_id);
        $form->hidden('administrator_id')->default($u->id);
        $form->text('name', __('Name'))->rules('required');
        $form->date('date', __('Date'))->default(date('Y-m-d'))->rules('required');
        $form->text('location', __('Location'))->rules('required');

        if ($u->isRole('admin')) {
            $users = User::where('sacco_id', $sacco_id)->get();
            $form->multipleSelect('members', __('Members'))
                ->options($users->pluck('name', 'id'))
                ->rules('required');
        } else {
            $form->hidden('members')->default($u->id);
        }

        $form->quill('minutes', __('Minutes'));
        $form->textarea('attendance', __('Attendance'));

        return $form;
    }
}
