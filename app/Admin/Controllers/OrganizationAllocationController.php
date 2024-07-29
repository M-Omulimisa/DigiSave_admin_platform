<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\VslaOrganisationSacco;
use App\Models\Sacco;
use App\Models\VslaOrganisation;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class OrganizationAllocationController extends AdminController
{
    protected $title = 'Organization Assignments';

    protected function grid()
    {
        $grid = new Grid(new VslaOrganisationSacco());

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

                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();

                $grid->model()
                    ->whereIn('sacco_id', $saccoIds)
                    ->whereHas('sacco', function ($query) {
                        $query->whereNotIn('status', ['deleted', 'inactive']);
                    })
                    ->orderBy('created_at', $sortOrder);

                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->orderBy('created_at', $sortOrder);
        }

        // Wrap fetching data in a try-catch block
        try {
            $grid->model()->paginate(10);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Error fetching data: ' . $e->getMessage());
            // Continue without the problematic data
            $grid->model()->whereNotNull('id')->paginate(10);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('organization.name', 'Organization')->sortable();
        $grid->column('sacco.name', 'Vsla Group')->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('chairperson_name', __('Chairperson Name'))
            ->display(function () {
                $chairperson = \App\Models\User::where('sacco_id', $this->sacco_id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $chairperson ? ucwords(strtolower($chairperson->name)) : '';
            });

        $grid->column('phone_number', __('Phone Number'))
            ->display(function () {
                $chairperson = \App\Models\User::where('sacco_id', $this->sacco_id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $chairperson ? $chairperson->phone_number : '';
            });

        $grid->column('created_at', __('Created At'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();

        // Adding search filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('organization.name', 'Organization');
            $filter->like('sacco.name', 'Vsla Group');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px; margin-left: 10px;">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                        Sort by Created Date <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="' . url()->current() . '?_sort=asc">Ascending</a></li>
                        <li><a href="' . url()->current() . '?_sort=desc">Descending</a></li>
                    </ul>
                </div>
            ');
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(VslaOrganisationSacco::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('organization.name', 'Organization');
        $show->field('sacco.name', 'Vsla Group');

        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new VslaOrganisationSacco());

    $admin = Admin::user();

    if (!$admin->isRole('admin')) {
        $orgAllocation = OrgAllocation::where('user_id', $admin->id)->first();
        if ($orgAllocation) {
            $saccoOptions = Sacco::where('vsla_organisation_id', $orgAllocation->vsla_organisation_id)
                                ->whereNotIn('status', ['deleted', 'inactive']) // Add status filtering here
                                ->pluck('name', 'id');
            $form->select('sacco_id', __('Vsla Group'))->options($saccoOptions)->rules('required');
        }
    } else {
        $form->select('sacco_id', __('Vsla Group'))
             ->options(Sacco::whereNotIn('status', ['deleted', 'inactive'])->pluck('name', 'id')) // Add status filtering here
             ->rules('required');
    }

    $form->select('vsla_organisation_id', __('Organization'))
         ->options(VslaOrganisation::pluck('name', 'id'))
         ->rules('required');

    $form->display('created_at', __('Created At'));
    $form->display('updated_at', __('Updated At'));

    $form->saving(function (Form $form) {
        $vslaOrganisationId = $form->vsla_organisation_id;
        $saccoId = $form->sacco_id;

        $existingRecord = VslaOrganisationSacco::where([
            'vsla_organisation_id' => $vslaOrganisationId,
            'sacco_id' => $saccoId,
        ])->first();

        if ($existingRecord) {
            admin_error('Error', 'The selected VSLA is already assigned to this organization.');
            return back()->withInput();
        }
    });

    $form->saved(function (Form $form) {
        $vslaOrganisationId = $form->vsla_organisation_id;
        $saccoId = $form->sacco_id;

        VslaOrganisationSacco::create([
            'vsla_organisation_id' => $vslaOrganisationId,
            'sacco_id' => $saccoId,
        ]);
    });

    return $form;
}

    // protected function form()
    // {
    //     $form = new Form(new VslaOrganisationSacco());

    //     $u = Admin::user();

    //     if (!$u->isRole('admin')) {
    //         if ($form->isCreating()) {
    //             admin_error("You are not allowed to create new Agent");
    //             return back();
    //         }
    //     }

    //     $form->display('id', 'ID');

    //     $form->select('vsla_organisation_id', 'Organization')
    //         ->options(VslaOrganisation::pluck('name', 'id'))
    //         ->rules('required');

    //     $form->select('sacco_id', 'Vsla Group')
    //         ->options(Sacco::pluck('name', 'id'))
    //         ->rules('required');

    //     $form->display('created_at', 'Created At');
    //     $form->display('updated_at', 'Updated At');

    //     $form->saving(function (Form $form) {
    //         $vslaOrganisationId = $form->vsla_organisation_id;
    //         $saccoId = $form->sacco_id;

    //         $existingRecord = VslaOrganisationSacco::where([
    //             'vsla_organisation_id' => $vslaOrganisationId,
    //             'sacco_id' => $saccoId,
    //         ])->first();

    //         if ($existingRecord) {
    //             admin_error('Error', 'The selected VSLA is already assigned to this organization.');
    //             return back()->withInput();
    //         }
    //     });

    //     $form->saved(function (Form $form) {
    //         $vslaOrganisationId = $form->vsla_organisation_id;
    //         $saccoId = $form->sacco_id;

    //         VslaOrganisationSacco::create([
    //             'vsla_organisation_id' => $vslaOrganisationId,
    //             'sacco_id' => $saccoId,
    //         ]);
    //     });

    //     return $form;
    // }
}
?>
