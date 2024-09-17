<?php

namespace App\Admin\Controllers;

use App\Models\MemberPosition;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use App\Models\User;
use App\Models\Utils;
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
    protected $title = 'VSLA Group Members';

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

    // Custom filter for members without gender
    $genderFilter = request()->get('_gender_filter', null);

    // Start building the model query
    $grid->model()->orderBy('created_at', $sortOrder);

    // Apply filters for non-admin users
    if (!$admin->isRole('admin')) {
        $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
        if ($orgAllocation) {
            $orgId = $orgAllocation->vsla_organisation_id;
            $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
            $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();

            // Restrict to specific sacco_ids
            $grid->model()->whereIn('sacco_id', $saccoIds);

            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
        }
    }

    // Exclude Admin type users (this will apply regardless of role or gender filter)
    $grid->model()->where(function ($query) {
        $query->whereNull('user_type')
              ->orWhere('user_type', '!=', 'Admin');
    });

    // If gender filter is active, filter members without gender (and exclude Admin members)
    if ($genderFilter === 'none') {
        $grid->model()->whereNull('sex')
            ->where(function ($query) {
                $query->whereNull('user_type')
                      ->orWhere('user_type', '!=', 'Admin');
            });
    }

    // Grid configuration
    $grid->disableBatchActions();
    $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')->placeholder('Search by name, email or phone number');

    $grid->column('first_name', __('First Name'))->sortable()->display(function ($firstName) {
        return ucwords(strtolower($firstName));
    });
    $grid->column('last_name', __('Last Name'))->sortable()->display(function ($lastName) {
        return ucwords(strtolower($lastName));
    });
    $grid->column('sex', __('Gender'))->sortable();
    $grid->column('phone_number', __('Phone Number'))->display(function ($phoneNumber) {
        // Check if the phone number is valid (Ugandan phone number format example)
        $isValidPhoneNumber = preg_match('/^(\+256|0)?[3-9][0-9]{8}$/', $phoneNumber);
        return $isValidPhoneNumber ? $phoneNumber : '';
    });
    $grid->column('sacco.name', __('Group Name'))->sortable();
    $grid->column('created_at', __('Date Joined'))->sortable()->display(function ($date) {
        return date('d M Y', strtotime($date));
    });

    // Adding search filters
    $grid->filter(function ($filter) {
        $filter->disableIdFilter();
        $filter->like('first_name', 'First Name');
        $filter->like('last_name', 'Last Name');
        $filter->like('email', 'Email');
        $filter->like('phone_number', 'Phone Number');
    });

    // Adding a custom button for filtering members without a gender
    $grid->tools(function ($tools) {
        // Add a custom button to filter members with no gender
        $tools->append('
            <div class="btn-group pull-right" style="margin-right: 10px;">
                <a href="'.url()->current().'?_gender_filter=none" class="btn btn-sm btn-default">Members Without Gender</a>
            </div>
        ');

        // Add a dropdown for sorting
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
        $show->field('district', __('District'));
        $show->field('subcounty', __('Subcounty'));
        $show->field('parish', __('Parish'));
        $show->field('village', __('Village'));

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
    // Form fields for editing a user
    $form->text('first_name', __('First Name'))->rules('required');
    $form->text('last_name', __('Last Name'))->rules('required');
    $form->text('phone_number', __('Phone Number'))->rules('required');
    $form->radio('sex', __('Gender'))
        ->options([
            'Male' => 'Male',
            'Female' => 'Female',
        ])->rules('required');

    $form->saving(function (Form $form) {
        // Additional logic can go here if needed
    });

    return $form;
}


}
?>
