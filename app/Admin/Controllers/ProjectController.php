<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use App\Models\Sacco;
use App\Models\VslaOrganisation;
use App\Models\OrgAllocation;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProjectController extends AdminController
{
    protected $title = 'Projects';

    protected function grid()
    {
        $grid = new Grid(new Project());

        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Apply filters based on user's role
        if (!$admin->isRole('admin')) {
            // Get the organization ID assigned to the user
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $grid->model()
                    ->where('vsla_organisation_id', $orgId)
                    ->orderBy('created_at', $sortOrder);
            } else {
                // If the user is not assigned to any organization, show no projects
                $grid->model()->whereNull('id'); // This will return an empty result
                $grid->disableCreateButton();
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()
                ->orderBy('created_at', $sortOrder);
        }

        // Show export button
        $grid->showExportBtn();
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by project name');

        // Define columns
        $grid->column('name', __('Project Name'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('description', __('Project Details'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        // Use 'organisation' instead of 'vslaOrganisation'
        $grid->column('organisation.name', __('Organization'))->sortable();

        $grid->column('saccos', __('Assigned Groups'))->display(function ($saccos) {
            $saccoNames = collect($saccos)->pluck('name')->map(function ($name) {
                return ucwords(strtolower($name));
            })->toArray();
            return implode(', ', $saccoNames);
        });

        $grid->column('start_date', __('Start Date'))->sortable();
        $grid->column('end_date', __('End Date'))->sortable();
        // $grid->column('created_at', __('Created At'))->display(function ($date) {
        //     return date('d M Y', strtotime($date));
        // })->sortable();

        // Filters
        $grid->filter(function ($filter) use ($admin) {
            $filter->disableIdFilter();

            $filter->like('name', 'Project Name');

            if ($admin->isRole('admin')) {
                $filter->equal('vsla_organisation_id', 'Organization')->select(VslaOrganisation::pluck('name', 'id'));
            }

            $filter->between('start_date', 'Start Date')->date();
            $filter->between('end_date', 'End Date')->date();
            $filter->between('created_at', 'Created At')->datetime();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Project::findOrFail($id));

        $show->field('name', __('Project Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });

        // Use 'organisation' instead of 'vslaOrganisation'
        $show->field('organisation.name', __('Organization'));

        $show->field('description', __('Description'))->unescape()->as(function ($description) {
            return nl2br($description);
        });

        $show->field('saccos', __('Assigned Groups'))->as(function ($saccos) {
            return $saccos->pluck('name')->map(function ($name) {
                return ucwords(strtolower($name));
            })->implode(', ');
        });

        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));

        $show->field('created_at', __('Created At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Project());

        $admin = Admin::user();
        $adminId = $admin->id;

        if ($form->isCreating() || $form->isEditing()) {
            // Get the organization ID assigned to the user
            if (!$admin->isRole('admin')) {
                $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
                if ($orgAllocation) {
                    $orgId = $orgAllocation->vsla_organisation_id;
                    $form->hidden('vsla_organisation_id')->value($orgId);
                } else {
                    admin_error('Error', 'You are not assigned to any organization.');
                    return back();
                }
            } else {
                // Admin can select any organization
                $form->select('vsla_organisation_id', __('Organization'))->options(VslaOrganisation::pluck('name', 'id'))->rules('required');
            }
        }

        $form->text('name', __('Project Name'))->rules('required');
        $form->textarea('description', __('Description'));
        $form->date('start_date', __('Start Date'))->default(date('Y-m-d'))->rules('required|date');
        $form->date('end_date', __('End Date'))->rules('nullable|date|after_or_equal:start_date');

        // Saccos selection
        $form->listbox('saccos', __('Assign Groups'))->options(function () use ($admin) {
            if ($admin->isRole('admin')) {
                // Admin can select from all saccos
                return Sacco::pluck('name', 'id');
            } else {
                // Non-admin users can select from saccos assigned to their organization
                $adminId = $admin->id;
                $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
                if ($orgAllocation) {
                    $orgId = $orgAllocation->vsla_organisation_id;
                    $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)
                        ->pluck('sacco_id')
                        ->toArray();
                    return Sacco::whereIn('id', $saccoIds)->pluck('name', 'id');
                } else {
                    return [];
                }
            }
        })->rules('required');

        $form->saving(function (Form $form) {
            // Validate date range
            if ($form->start_date && $form->end_date && $form->start_date > $form->end_date) {
                admin_error('Validation Error', 'End Date must be after or equal to Start Date.');
                return back()->withInput();
            }

            // For non-admin users, ensure the vsla_organisation_id is set
            $admin = Admin::user();
            if (!$admin->isRole('admin')) {
                $orgAllocation = OrgAllocation::where('user_id', $admin->id)->first();
                if ($orgAllocation) {
                    $form->vsla_organisation_id = $orgAllocation->vsla_organisation_id;
                }
            }
        });

        $form->saved(function (Form $form) {
            // Sync saccos
            $project = $form->model();
            $saccoIds = $form->saccos;

            // Validate that the selected saccos belong to the same organization
            $validSaccoIds = Sacco::whereIn('id', $saccoIds)->pluck('id')->toArray();

            $project->saccos()->sync($validSaccoIds);
        });

        return $form;
    }
}
