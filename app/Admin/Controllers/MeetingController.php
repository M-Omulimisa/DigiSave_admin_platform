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

        $u = Auth::user();
        $adminId = $u->id;

        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $grid->model()->where('status', '!=', 'deleted');

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->whereIn('id', $saccoIds)
                    ->where(function ($query) {
                        $query->whereHas('users', function ($query) {
                            $query->whereHas('position', function ($query) {
                                $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                            })->whereNotNull('phone_number')
                                ->whereNotNull('name');
                        });
                    })
                    ->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()->where(function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })->whereNotNull('phone_number')
                        ->whereNotNull('name');
                });
            })
                ->orderBy('created_at', $sortOrder);
        }

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Filter by group name
            $filter->equal('sacco_id', 'Group Name')->select(Sacco::all()->pluck('name', 'id'));

            // Filter by cycle name
            $filter->where(function ($query) {
                $cycleName = $this->input;
                $cycleIds = Cycle::where('name', 'like', "%$cycleName%")->pluck('id');
                $query->whereIn('cycle_id', $cycleIds);
            }, 'Cycle Name');
        });

        $grid->column('sacco.name', __('Group Name'));
        // Display the cycle name directly using its id
        $grid->column('cycle_id', __('Cycle'))->display(function ($cycleId) {
            $cycle = Cycle::find($cycleId);
            return $cycle ? $cycle->name : 'Unknown';
        });
        $grid->column('name', __('Meeting'))->editable()->sortable();
        $grid->column('date', __('Date'));
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
            $memberIds = json_decode($members, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($memberIds) && !empty($memberIds)) {
                // Filter out any non-numeric IDs
                $validMemberIds = array_filter($memberIds, function ($id) {
                    return is_numeric($id);
                });

                if (!empty($validMemberIds)) {
                    $memberNames = User::whereIn('id', $validMemberIds)->get()->pluck('name')->toArray();
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


        // Update the 'minutes' column
        $grid->column('minutes', __('Minutes'))->display(function ($minutes) {
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
