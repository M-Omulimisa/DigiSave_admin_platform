<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\User;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MembersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VSLA Members';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

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
                $grid->model()->whereIn('sacco_id', $saccoIds)->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            $grid->model()->whereNull('user_type')->orderBy('created_at', $sortOrder);
        }

        $grid->disableBatchActions();
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')->placeholder('Search by name, email or phone number');

        $grid->column('first_name', __('First Name'))->sortable()->display(function ($firstName) {
            return ucwords(strtolower($firstName));
        });
        $grid->column('last_name', __('Last Name'))->sortable()->display(function ($lastName) {
            return ucwords(strtolower($lastName));
        });
        $grid->column('sex', __('Gender'))->sortable();
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('sacco.name', __('Group Name'))->sortable();
        $grid->column('created_at', __('Date Joined'))->sortable()->display(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $grid->column('location_lat', __('Latitude'))->sortable();
        $grid->column('location_long', __('Longitude'))->sortable();

        // Adding search filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('email', 'Email');
            $filter->like('phone_number', 'Phone Number');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px; margin-left: 10px;">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                        Sort by Created Date <span class="caret"></span>
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

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('first_name', __('First Name'))->as(function ($firstName) {
            return ucwords(strtolower($firstName));
        });
        $show->field('last_name', __('Last Name'))->as(function ($lastName) {
            return ucwords(strtolower($lastName));
        });
        $show->field('sex', __('Gender'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('created_at', __('Date Joined'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $show->field('location_lat', __('Latitude'));
        $show->field('location_long', __('Longitude'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());
        $u = Admin::user();

        $form->text('first_name', __('First Name'))
            ->rules('required')->help('Ensure the first letter of each word is capitalized.');
        $form->text('last_name', __('Last Name'))
            ->rules('required')->help('Ensure the first letter of each word is capitalized.');
        $form->text('phone_number', __('Phone Number'))
            ->rules('required');
        $form->radio('sex', __('Gender'))
            ->options([
                'Male' => 'Male',
                'Female' => 'Female',
            ])
            ->rules('required');

        $form->text('location_lat', __('Latitude'));
        $form->text('location_long', __('Longitude'));

        return $form;
    }
}
?>
