<?php

namespace App\Admin\Controllers;

use App\Models\Cycle;
use App\Models\Meeting;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Log;

class CycleMeetingController extends AdminController
{
    protected $title = 'Meetings';

    public function index(Content $content)
    {
        // Get sacco_id and cycle_id from request
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        // Fetch the cycle and group (Sacco) name
        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';

        $title = "{$groupName} - Cycle {$cycle->name} Meetings";

        return $content
            ->header($title)
            ->body($this->grid($cycleId, $saccoId));
    }

    protected function grid($cycleId, $saccoId)
    {
        $grid = new Grid(new Meeting());

        // Apply filters based on request parameters
        if ($saccoId) {
            $grid->model()->where('sacco_id', $saccoId);
        }

        if ($cycleId) {
            $grid->model()->where('cycle_id', $cycleId);
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
        $grid->column('chairperson_name', __('Chairperson Name'))->sortable();
        $grid->column('members', __('Attendance'))->display(function ($members) {
            $memberData = json_decode($members, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($memberData) && !empty($memberData)) {
                $present = isset($memberData['present']) ? $memberData['present'] : 0;
                $absent = isset($memberData['absent']) ? $memberData['absent'] : 0;
                return "Present: $present, Absent: $absent";
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
            $memberData = json_decode($members, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($memberData) && !empty($memberData)) {
                $present = isset($memberData['present']) ? $memberData['present'] : 0;
                $absent = isset($memberData['absent']) ? $memberData['absent'] : 0;
                return "Present: $present, Absent: $absent";
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
    $form = new Form(new Meeting());

    // Get sacco_id and cycle_id from the request parameters
    $saccoId = request()->get('sacco_id');
    $cycleId = request()->get('cycle_id');

    $form->hidden('sacco_id')->default($saccoId);
    $form->hidden('cycle_id')->default($cycleId);
    $form->hidden('administrator_id')->default(Admin::user()->id);

    $form->text('name', __('Meeting Name'))->rules('required');
    $form->date('date', __('Date'))->default(date('Y-m-d'))->rules('required');

    // Add a field for members with a present/absent toggle
    $form->table('members', __('Members'), function ($table) {
        $table->text('name', __('Name'))->rules('required');
        $table->select('status', __('Status'))->options(['present' => 'Present', 'absent' => 'Absent'])->rules('required');
    });

    $form->embeds('minutes', __('Minutes'), function ($form) {
        $form->embeds('opening_summary', __('Opening Summary'), function ($form) {
            $form->text('total_savings', __('Total Savings'))->rules('required');
            $form->text('total_loans', __('Total Loans'))->rules('required');
            $form->text('total_fines', __('Total Fines'))->rules('required');
            $form->text('total_welfare', __('Total Welfare'))->rules('required');
            $form->text('number_of_loans_disbursed', __('Number of Loans Disbursed'))->rules('required');
            $form->text('welfare_disbursed', __('Welfare Disbursed'))->rules('required');
            $form->text('loan_payments_made', __('Loan Payments Made'))->rules('required');
        });

        $form->embeds('closing_summary', __('Closing Summary'), function ($form) {
            $form->text('total_savings', __('Total Savings'))->rules('required');
            $form->text('total_loans', __('Total Loans'))->rules('required');
            $form->text('total_fines', __('Total Fines'))->rules('required');
            $form->text('total_welfare', __('Total Welfare'))->rules('required');
            $form->text('number_of_loans_disbursed', __('Number of Loans Disbursed'))->rules('required');
            $form->text('welfare_disbursed', __('Welfare Disbursed'))->rules('required');
            $form->text('loan_payments_made', __('Loan Payments Made'))->rules('required');
        });
    });

    return $form;
}

}
?>
