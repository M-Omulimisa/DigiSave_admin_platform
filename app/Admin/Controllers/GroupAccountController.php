<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\User;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Log;

class GroupAccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Group Accounts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $u = Admin::user();
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
                ->whereIn('sacco_id',
                    $saccoIds
                )
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->whereHas('sacco.users', function ($query) {
                    $query->whereIn('position_id', function ($subQuery) {
                        $subQuery->select('id')
                                 ->from('positions')
                                 ->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    });
                })
                ->where('user_type', 'Admin')->orderBy('created_at', $sortOrder)
                ->orderBy('created_at', $sortOrder);;
                // $grid->model()->whereIn('sacco_id', $saccoIds)->orderBy('created_at', $sortOrder);
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
                ->whereHas('sacco.users', function ($query) {
                    $query->whereIn('position_id', function ($subQuery) {
                        $subQuery->select('id')
                                 ->from('positions')
                                 ->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    });
                })
                ->where('user_type', 'Admin')
                ->orderBy('created_at', $sortOrder);
        }

        // Wrap fetching data in a try-catch block
        try {
            $grid->model()->where('user_type', 'Admin')->paginate(10);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Error fetching data: ' . $e->getMessage());
            // Continue without the problematic data
            $grid->model()->where('user_type', 'Admin')->whereNotNull('id')->paginate(10);
        }

        $grid->disableBatchActions();
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')->placeholder('Search by name, email, or phone number');

        $grid->column('sacco.name', __('Group Name'))->sortable();

        $grid->column('name', __('Account Name'))->display(function () {
            return ucwords(strtolower($this->first_name . ' ' . $this->last_name));
        });

        $grid->column('phone_number', __('Group Code'));
        $grid->column('location_lat', __('Latitude'))->sortable();
        $grid->column('location_long', __('Longitude'))->sortable();
        $grid->column('created_at', __('Created At'))->display(function ($createdAt) {
            return \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i:s');
        })->sortable();
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
                        <li><a href="' . url()->current() . '?_sort=asc">Ascending</a></li>
                        <li><a href="' . url()->current() . '?_sort=desc">Descending</a></li>
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
        $show->field('phone_number', __('Group Code'));
        $show->field('location_lat', __('Latitude'));
        $show->field('location_long', __('Longitude'));
        $show->field('created_at', __('Created At'));

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

    // Dropdown to select Sacco
    $form->select('sacco_id', __('Select Group/Sacco'))
        ->options(Sacco::all()->pluck('name', 'id'))
        ->rules('required')
        ->help('Select the group or Sacco the member belongs to');

    // Input field for account name
    $form->text('name', __('Account Name'))
        ->rules('required')
        ->help('Enter the account name for this group');

    // Auto-generate 7-character alphanumeric Group Code as phone number
    $form->text('phone_number', __('Group Code'))
        ->default(function () {
            return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7));
        })
        ->readonly()
        ->help('A unique group code will be auto-generated');

    // Latitude and Longitude fields (nullable)
    $form->text('location_lat', __('Latitude'))
        ->help('Optional: Enter the latitude');

    $form->text('location_long', __('Longitude'))
        ->help('Optional: Enter the longitude');

    // Hidden fields with default values
    $form->hidden('user_type')->default('Admin');
    $form->hidden('sacco_join_status')->default('Approved');
    $form->hidden('processed')->default('Yes');
    $form->hidden('process_status')->default('approved');

    // First and last name (extracted from the name)
    $form->hidden('first_name');
    $form->hidden('last_name');

    // Hidden field for username
    $form->hidden('username');

    // Before saving, validate and split the name into first and last names
    $form->saving(function (Form $form) {
        // Check for existing Admin in the selected Sacco
        if (User::where('sacco_id', $form->sacco_id)->where('user_type', 'Admin')->exists()) {
            throw new \Exception("An account already exists for this Group.");
        }

        // Set username to the generated phone number (Group Code)
        $form->username = $form->phone_number;

        // Split name into first and last names
        if ($form->name) {
            $nameParts = explode(' ', $form->name, 2);
            $form->first_name = $nameParts[0];
            $form->last_name = $nameParts[1] ?? ''; // Assign last name if available
        }
    });

    return $form;
}

    }
?>
