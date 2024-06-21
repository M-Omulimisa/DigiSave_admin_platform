<?php

namespace App\Admin\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
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

        if (!$u->isRole('admin')) {
            // Apply filters based on user roles if necessary
            $grid->model()->where('sacco_id', $u->sacco_id)->where('administrator_id', $u->id);
        }

        $grid->column('sacco.name', __('Group Name'));
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

        $grid->column('members', __('Attendance'))->display(function ($attendance) {
            $attendanceIds = explode(',', str_replace(['[', ']', ' '], '', $attendance));
            if (is_array($attendanceIds) && !empty($attendanceIds[0])) {
                $members = User::whereIn('id', $attendanceIds)->pluck('name')->toArray();
                return '<ul>' . implode('', array_map(function ($name) {
                    return '<li>' . ucwords(strtolower($name)) . '</li>';
                }, $members)) . '</ul>';
            }
            return '';
        });

        $grid->column('minutes', __('Minutes'))->display(function ($minutes) {
            return nl2br(e($minutes));
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
        $show->field('members', __('Members'))->as(function ($attendance) {
            $attendanceIds = explode(',', str_replace(['[', ']', ' '], '', $attendance));
            if (is_array($attendanceIds) && !empty($attendanceIds[0])) {
                $members = User::whereIn('id', $attendanceIds)->pluck('name')->toArray();
                return '<ul>' . implode('', array_map(function ($name) {
                    return '<li>' . ucwords(strtolower($name)) . '</li>';
                }, $members)) . '</ul>';
            }
            return '';
        });
        $show->field('minutes', __('Minutes'))->as(function ($minutes) {
            return nl2br(e($minutes));
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
