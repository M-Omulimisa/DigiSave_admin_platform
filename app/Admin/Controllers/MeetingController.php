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

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('sacco_id', 'Group Name')->select(Sacco::all()->pluck('name', 'id'));
            $filter->like('sacco.district', 'District');
            $filter->where(function ($query) {
                $cycleName = $this->input;
                $cycleIds = Cycle::where('name', 'like', "%$cycleName%")->pluck('id');
                $query->whereIn('cycle_id', $cycleIds);
            }, 'Cycle Name');
        });

        // Grid columns
        $grid->column('sacco.name', __('Group Name'))->sortable();
        $grid->column('sacco.district', __('District'))->sortable()->display(function ($district) {
            return $district ? ucwords(strtolower($district)) : 'N/A';
        });
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
        })->editable()->sortable();
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

        $grid->column('members', __('Attendance'))->display(function ($members) {
            $memberData = json_decode($members, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($memberData) && !empty($memberData)) {
                if (isset($memberData['presentMembersIds']) && is_array($memberData['presentMembersIds'])) {
                    $memberNames = array_map(function ($member) {
                        return $member['name'];
                    }, $memberData['presentMembersIds']);

                    $formattedMembers = '<div class="card-deck">';
                    foreach ($memberNames as $name) {
                        $formattedMembers .= '<div class="card text-white bg-info mb-3" style="max-width: 18rem;">';
                        $formattedMembers .= '<div class="card-body"><h5 class="card-title">' . $name . '</h5></div>';
                        $formattedMembers .= '</div>';
                    }
                    $formattedMembers .= '</div>';
                    return $formattedMembers;
                }
            }
            return 'No attendance recorded';
        });

        $grid->column('minutes', __('Minutes'))->display(function ($minutes) {
            $minutesData = json_decode($minutes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $formattedMinutes = '<div class="row">';
                foreach ($minutesData as $section => $items) {
                    $formattedMinutes .= '<div class="col-md-6"><div class="card"><div class="card-body">';
                    $formattedMinutes .= '<h5 class="card-title">' . ucfirst(str_replace('_', ' ', $section)) . ':</h5><ul class="list-group list-group-flush">';
                    foreach ($items as $item) {
                        if (isset($item['title']) && isset($item['value'])) {
                            $formattedMinutes .= '<li class="list-group-item">' . $item['title'] . ': ' . $item['value'] . '</li>';
                        } else {
                            return 'No minutes data recorded';
                        }
                    }
                    $formattedMinutes .= '</ul></div></div></div>';
                }
                $formattedMinutes .= '</div>';
                return $formattedMinutes;
            }
            return $minutes;
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
