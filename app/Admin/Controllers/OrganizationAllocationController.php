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

        if (!is_string($sortOrder)) {
            $sortOrder = 'desc';
        }

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;

                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();

                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgId)->pluck('user_id')->toArray();

                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->where('vsla_organisation_id', $orgId)->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()->orderBy('created_at', $sortOrder);
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
        $grid->column('created_at', __('Updated At'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();

        // Adding search filters
        $grid->filter(function ($filter) {
            // Remove the default ID filter
            $filter->disableIdFilter();

            // Add filter for organization name
            $filter->like('organization.name', 'Organization');

            // Add filter for VSLA group name
            $filter->like('sacco.name', 'Vsla Group');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                        Sort by Established <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="'.url()->current().'?_sort=asc">Ascending</a></li>
                        <li><a href="'.url()->current().'?_sort=desc">Descending</a></li>
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

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->display('id', 'ID');

        $form->select('vsla_organisation_id', 'Organization')
            ->options(VslaOrganisation::pluck('name', 'id'))
            ->rules('required');

        $form->multipleSelect('sacco_id', 'Vsla Groups')
            ->options(Sacco::pluck('name', 'id'))
            ->rules('required');

        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');

        $form->saving(function (Form $form) {
            if (is_array($form->sacco_id)) {
                foreach ($form->sacco_id as $saccoId) {
                    VslaOrganisationSacco::create([
                        'vsla_organisation_id' => $form->vsla_organisation_id,
                        'sacco_id' => $saccoId,
                    ]);
                }
                // Prevent saving of the form itself
                return false;
            }
        });

        return $form;
    }
}
?>
