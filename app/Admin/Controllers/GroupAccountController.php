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
                $grid->model()->whereIn('sacco_id', $saccoIds)->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            $grid->model()->where('user_type', 'Admin')->orderBy('created_at', $sortOrder);
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

        $grid->column('name', __('Account Name'))->display(function () {
            return ucwords(strtolower($this->first_name . ' ' . $this->last_name));
        });

        $grid->column('phone_number', __('Group Code'));
        $grid->column('location_lat', __('Latitude'))->sortable();
        $grid->column('location_long', __('Longitude'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();

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
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('first_name', __('First Name'))->as(function ($firstName) {
            return ucwords(strtolower($firstName));
        });
        $show->field('last_name', __('Last Name'))->as(function ($lastName) {
            return ucwords(strtolower($lastName));
        });
        $show->field('reg_date', __('Reg Date'));
        $show->field('last_seen', __('Last Seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile Photo'));
        $show->field('user_type', __('User Type'));
        $show->field('sex', __('Sex'));
        $show->field('reg_number', __('Reg Number'));
        $show->field('country', __('Country'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile Photo Large'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('location_lat', __('Location Lat'));
        $show->field('location_long', __('Location Long'));
        $show->field('facebook', __('Facebook'));
        $show->field('twitter', __('Twitter'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('linkedin', __('Linkedin'));
        $show->field('website', __('Website'));
        $show->field('other_link', __('Other Link'));
        $show->field('cv', __('Cv'));
        $show->field('language', __('Language'));
        $show->field('about', __('About'));
        $show->field('address', __('Address'))->as(function ($address) {
            return ucwords(strtolower($address));
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        $show->field('remember_token', __('Remember Token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
        $show->field('campus_id', __('Campus Id'));
        $show->field('complete_profile', __('Complete Profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('intro', __('Intro'));
        $show->field('sacco_id', __('Sacco Id'));
        $show->field('sacco_join_status', __('Sacco Join Status'));
        $show->field('id_front', __('Id Front'));
        $show->field('id_back', __('Id Back'));
        $show->field('status', __('Status'));
        $show->field('balance', __('Balance'));

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

        if ((!$u->isRole('admin')) && (!$u->isRole('sacco'))) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Sacco");
                return back();
            }
        }

        if (!$u->isRole('admin')) {
            $form->hidden('sacco_id', __('Sacco'))->default($u->sacco_id)->readonly();
        } else {
            $form->select('sacco_id', __('Sacco'))->options(\App\Models\Sacco::pluck('name', 'id'))->rules('required');
        }

        $form->text('first_name', __('First Name'))
            ->rules('required');
        $form->text('last_name', __('Last Name'))
            ->rules('required');
        $form->text('phone_number', __('Phone Number'))
            ->rules('required');

        $form->radio('sex', __('Gender'))
            ->options([
                'Male' => 'Male',
                'Female' => 'Female',
            ])
            ->rules('required');

        $form->text('address', __('Address'));

        $form->image('avatar', __('Passport Size Photo'));
        $form->image('profile_photo_large', __('National ID Front'));
        $form->image('profile_photo', __('National ID Back'));

        $form->datetime('dob', __('Date of Birth'))->default(date('Y-m-d H:i:s' . strtotime('-18 years')));

        $form->image('id_front', __('Id Front'));
        $form->image('id_back', __('Id Back'));
        $form->hidden('user_type', __('User Type'))->default('Member');

        $form->divider('MEMBERSHIP STATUS');

        $form->radioCard('status', __('User Status'))
            ->options([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
            ]);

        $form->radioCard('sacco_join_status', __('Membership Status'))
            ->options([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
            ]);

        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->ignore(['password_confirmation']);
        $form->ignore(['change_password']);

        return $form;
    }
}
?>
