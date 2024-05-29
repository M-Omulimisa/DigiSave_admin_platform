<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SaccoController extends AdminController
{
    protected $title = 'VSLA Groups';

    protected function grid()
    {
        $grid = new Grid(new Sacco());

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
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->whereIn('id', $saccoIds)->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()->orderBy('created_at', $sortOrder);
        }

        $grid->showExportBtn();
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');

        $grid->column('name', __('Name'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('phone_number', __('Phone Number'))
            ->sortable()
            ->display(function () {
                $chairperson = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $chairperson ? $chairperson->phone_number : '';
            });

        $grid->column('share_price', __('Share (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable();

        $grid->column('physical_address', __('Physical Address'))->sortable()->display(function ($address) {
            return ucwords(strtolower($address));
        });

        $grid->column('created_at', __('Established'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();

        $grid->column('chairperson_name', __('Chairperson Name'))
            ->sortable()
            ->display(function () {
                $chairperson = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $chairperson ? ucwords(strtolower($chairperson->name)) : '';
            });

        // Adding search filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('name', 'Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('physical_address', 'Physical Address');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px; margin-left: 10px;">
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
        $show = new Show(Sacco::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        $show->field('name', __('Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });
        $show->field('phone_number', __('Phone Number'));
        $show->field('email_address', __('Email Address'));
        $show->field('physical_address', __('Physical Address'))->as(function ($address) {
            return ucwords(strtolower($address));
        });
        $show->field('created_at', __('Establishment Date'));
        $show->field('registration_number', __('Registration Number'));
        $show->field('chairperson_name', __('Chairperson Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });
        $show->field('chairperson_phone_number', __('Chairperson Phone Number'));
        $show->field('chairperson_email_address', __('Chairperson Email Address'));
        $show->field('about', __('About'));
        $show->field('terms', __('Terms'));
        $show->field('mission', __('Mission'));
        $show->field('vision', __('Vision'));
        $show->field('logo', __('Logo'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Sacco());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Sacco");
                return back();
            }
        } else {
            $ajax_url = url(
                '/api/ajax?'
                    . "search_by_1=name"
                    . "&search_by_2=id"
                    . "&model=User"
            );
            $form->select('administrator_id', "Group Administrator")
                ->options(function ($id) {
                    $a = Administrator::find($id);
                    if ($a) {
                        return [$a->id => "#" . $a->id . " - " . $a->name];
                    }
                })
                ->ajax($ajax_url)->rules('required');
        }

        $form->text('name', __('Name'))->rules('required');
        $form->decimal('share_price', __('Share Price'))
            ->help('UGX')
            ->rules('required|numeric|min:0');
        $form->text('phone_number', __('Phone Number'))->rules('required');
        $form->text('email_address', __('Email Address'));
        $form->text('physical_address', __('Physical Address'));
        $form->datetime('created_at', __('Establishment Date'))->rules('required');
        $form->image('logo', __('VSLA Logo'));

        return $form;
    }
}
?>
