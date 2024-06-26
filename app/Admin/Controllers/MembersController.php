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

        // Restrict access for non-admin users
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()
                    ->whereIn('sacco_id', $saccoIds)
                    ->where(function ($query) {
                        $query->whereNull('user_type')
                              ->orWhere('user_type', '!=', 'Admin');
                    })
                    ->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            $grid->model()
                ->where(function ($query) {
                    $query->whereNull('user_type')
                          ->orWhere('user_type', '!=', 'Admin');
                })
                ->orderBy('created_at', $sortOrder);
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
        $grid->column('phone_number', __('Phone Number'))->display(function ($phoneNumber) {
            // Check if the phone number is valid (Ugandan phone number format example)
            $isValidPhoneNumber = preg_match('/^(\+256|0)?[3-9][0-9]{8}$/', $phoneNumber);
            return $isValidPhoneNumber ? $phoneNumber : '';
        });
        $grid->column('sacco.name', __('Group Name'))->sortable();
        $grid->column('created_at', __('Date Joined'))->sortable()->display(function ($date) {
            return date('d M Y', strtotime($date));
        });
        // $grid->column('district', __('District'))->sortable();
        // $grid->column('subcounty', __('Subcounty'))->sortable();
        // $grid->column('parish', __('Parish'))->sortable();
        // $grid->column('village', __('Village'))->sortable();

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
        $form->select('sacco_id', __('Group'))
            ->options(Sacco::all()->pluck('name', 'id'))
            ->rules('required');
        $form->select('position_id', __('Position'))
            ->options(MemberPosition::all()->pluck('name', 'id'))
            ->rules('required');
        $form->text('district', __('District'))->rules('required');
        $form->text('subcounty', __('Subcounty'))->rules('required');
        $form->text('parish', __('Parish'))->rules('required');
        $form->text('village', __('Village'))->rules('required');

        $form->saving(function (Form $form) {
            // Perform any necessary logic before saving the form
        });

        $form->saved(function (Form $form) {
            $position = MemberPosition::find($form->position_id)->name;
            $sacco = Sacco::find($form->sacco_id);
            $saccoPhoneNumber = $sacco->phone_number;

            if (in_array($position, ['Chairperson', 'Secretary', 'Treasurer'])) {
                $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $user = User::find($form->model()->id);
                $user->password = bcrypt($password);

                if (!$user->save()) {
                    admin_error('Error', 'Failed to create account. Please try again.');
                    return;
                }

                $message = "Success, admin account for Position: $position has been created successfully. Use Phone number: {$form->phone_number} and Passcode: $password to login into your VSLA group. Group passcode: $saccoPhoneNumber";

                // Validate the phone number and send SMS
                if (Utils::phone_number_is_valid($form->phone_number)) {
                    try {
                        $resp = Utils::send_sms($form->phone_number, $message);
                        if ($resp) {
                            admin_success($message);
                        } else {
                            admin_warning('Warning', 'Group member ' . $form->first_name . ' ' . $form->last_name . ' created successfully, but failed to send login credentials.');
                        }
                    } catch (\Exception $e) {
                        admin_error('Error', 'Failed to send SMS. Please check the phone number and try again.');
                    }
                } else {
                    admin_warning('Warning', 'Group member ' . $form->first_name . ' ' . $form->last_name . ' created successfully, but the phone number is invalid.');
                }
            } else {
                admin_success('Success', 'Group member ' . $form->first_name . ' ' . $form->last_name . ' created successfully');
            }
        });

        return $form;
    }
}
?>
