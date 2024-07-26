<?php

namespace App\Admin\Controllers;

use App\Models\Cycle;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CycleController extends AdminController
{
    protected $title = 'Cycles';

    protected function grid()
    {
        $grid = new Grid(new Cycle());
        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Restrict access for non-admin users
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $organizationAssignments = Sacco::where('vsla_organisation_id', $orgId)->whereNotIn('status', ['deleted', 'inactive'])->get();
                $saccoIds = $organizationAssignments->pluck('id')->toArray();

                $grid->model()
                    ->whereHas('sacco', function ($query) use ($saccoIds) {
                        $query->whereIn('id', $saccoIds)
                              ->whereNotIn('status', ['deleted', 'inactive']);
                    })
                    ->orderBy('created_at', $sortOrder);

                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            $grid->model()
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->orderBy('created_at', $sortOrder);
        }

        $grid->disableBatchActions();
        $grid->disableExport();

        // Add Sacco name column
        $grid->column('sacco.name', __('Group'))->sortable();

        // Add suggestive search for group name
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('sacco_id', 'Group')->select(Sacco::all()->pluck('name', 'id'));
        });

        $grid->column('name', __('Cycle Name'))->sortable();
        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return date('d M, Y', strtotime($date));
            })->sortable();
        $grid->column('end_date', __('End Date'))
            ->display(function ($date) {
                return date('d M, Y', strtotime($date));
            })->sortable();
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Inactive' => 'warning',
            ])->sortable();
        $grid->column('created_at', __('Created At'))
            ->display(function ($date) {
                return date('d M, Y', strtotime($date));
            })->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Cycle::findOrFail($id));

        $show->field('sacco.name', __('Group Name'));
        $show->field('name', __('Name'));
        $show->field('start_date', __('Start date'))->as(function ($date) {
            return date('d M, Y', strtotime($date));
        });
        $show->field('end_date', __('End date'))->as(function ($date) {
            return date('d M, Y', strtotime($date));
        });
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'))->as(function ($date) {
            return date('d M, Y H:i:s', strtotime($date));
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Cycle());

        $admin = Admin::user();
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $admin->id)->first();
            if ($orgAllocation) {
                $saccoOptions = Sacco::where('vsla_organisation_id', $orgAllocation->vsla_organisation_id)
                                    ->pluck('name', 'id');
                $form->select('sacco_id', __('Select Group'))->options($saccoOptions)->rules('required');
            }
        } else {
            $form->select('sacco_id', __('Select Group'))->options(Sacco::all()->pluck('name', 'id'))->rules('required');
        }

        $form->text('name', __('Cycle Name'))->rules('required');
        $form->date('start_date', __('Start date'))->default(date('Y-m-d'))->rules('required');
        $form->date('end_date', __('End date'))->default(date('Y-m-d'))->rules('required');

        // Date range validation for start and end date
        $form->saving(function (Form $form) {
            if ($form->start_date > $form->end_date) {
                admin_error('Start date cannot be greater than end date');
                return back();
            }
        });

        $form->radio('status', __('Status'))
            ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
            ->default('Inactive');
        $form->text('amount_required_per_meeting', __('Welfare Fund'))->rules('required');
        // $form->textarea('description', __('Description'));

        // Created_by_id hidden field
        $form->hidden('created_by_id')->value(Admin::user()->id);

        return $form;
    }
}
?>
