<?php

namespace App\Admin\Controllers;

use App\Models\Meeting;
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
            // $grid->disableCreateButton();
            // $grid->actions(function (Grid\Displayers\Actions $actions) {
            //     $actions->disableDelete();
            // });
            // $grid->disableFilter();

            // Ensure the user is the Sacco admin and the administrator_id matches
            // $grid->model()->where('sacco_id', $u->sacco_id)->where('administrator_id', $u->id);
        }

        // $grid->model()->where('sacco_id', $u->sacco_id);
        // $grid->column('id', __('Id'));
        $grid->column('sacco_id', __('Group Name'));
        $grid->column('name', __('Meeting'))->editable()->sortable();
        $grid->column('date', __('Date'));
        $grid->column('administrator_id', __('Administrator id'));
        $grid->column('members', __('Attendence'));
        $grid->column('minutes', __('Minutes'));

        return $grid;
    }

    // Other methods: detail() and form() remain unchanged


// Other methods: detail() and form() remain unchanged


    protected function detail($id)
    {
        $show = new Show(Meeting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('date', __('Date'));
        $show->field('location', __('Location'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('administrator_id', __('Administrator id'));
        $show->field('members', __('Members'));
        $show->field('minutes', __('Minutes'));
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

        // Only allow selecting members if the user is the administrator
        if ($u->isRole('admin')) {
            $users = \App\Models\User::where('sacco_id', $sacco_id)->get();
            $form->multipleSelect('members', __('Members'))
                ->options($users->pluck('name', 'id'))
                ->rules('required');
        } else {
            $form->hidden('members')->default($u->id); // Automatically assign the admin as the member
        }

        $form->quill('minutes', __('Minutes'));
        $form->textarea('attendance', __('Attendance'));

        return $form;
    }

}
