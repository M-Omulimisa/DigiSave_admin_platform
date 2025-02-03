<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\OrgAllocation;
use App\Models\OrganizationAssignment;
use App\Models\User;
use App\Models\VslaOrganisation;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Utils;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Facades\Hash;

class AssignOrganisationAdminController extends AdminController
{
    protected $title = 'Assign Org Admins';

    protected function grid()
    {
        $grid = new Grid(new OrgAllocation());
        $admin = Admin::user();
        $adminId = $admin->id;
        $sortOrder = request()->get('_sort', 'desc');
        if (!is_string($sortOrder)) {
            $sortOrder = 'desc';
        }

        $grid->header(function ($query) use ($admin) {
            $organizations = VslaOrganisation::pluck('name', 'id')->toArray();
            $regions = OrgAllocation::whereNotNull('region')
                ->where('region', '!=', '')
                ->distinct()
                ->pluck('region', 'region')
                ->toArray();

            return '
            <div class="quick-search" style="margin-bottom: 20px;">
                <form action="" method="get" style="display: flex; gap: 15px; align-items: center;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="organization" class="form-control" style="min-width: 200px;">
                            <option value="">Select Organization</option>
                            '.implode('', array_map(function($id, $name) {
                                $selected = request('organization') == $id ? 'selected' : '';
                                return "<option value=\"{$id}\" {$selected}>{$name}</option>";
                            }, array_keys($organizations), $organizations)).'
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="region" class="form-control" style="min-width: 200px;">
                            <option value="">Select Region</option>
                            '.implode('', array_map(function($region) {
                                $selected = request('region') == $region ? 'selected' : '';
                                return "<option value=\"{$region}\" {$selected}>{$region}</option>";
                            }, $regions)).'
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="'.url()->current().'" class="btn btn-warning">Reset</a>
                    </div>
                </form>
            </div>';
        });

        if (request('organization')) {
            $grid->model()->where('vsla_organisation_id', request('organization'));
        }

        if (request('region')) {
            $grid->model()->where('region', request('region'));
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
            }
        } else {
            $grid->model()->orderBy('created_at', $sortOrder);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('admin', 'Admin')->display(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        })->sortable();
        $grid->column('admin_email', 'Admin Email')->display(function () {
            return $this->admin ? $this->admin->email : 'N/A';
        });
        $grid->column('admin_phone', 'Admin Phone')->display(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
        $grid->column('organization', 'VSLA Organisation')->display(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        })->sortable();
        $grid->column('position', 'Position')->sortable();
        $grid->column('region', 'Region')->sortable();
        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('admin.first_name', 'First Name');
            $filter->like('admin.last_name', 'Last Name');
            $filter->like('organization.name', 'Organization');
            $filter->like('position', 'Position');
            $filter->like('region', 'Region');
        });

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
        $show = new Show(OrgAllocation::findOrFail($id));
        $show->field('id', 'ID');
        $show->field('admin', 'Admin')->as(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        });
        $show->field('admin_phone', 'Admin Phone')->as(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
        $show->field('organization', 'VSLA Organisation')->as(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        });
        $show->field('position', 'Position');
        $show->field('region', 'Region');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');
        return $show;
    }

    protected function form()
    {
        $form = new Form(new OrgAllocation());
        $admin = Admin::user();
        $adminId = $admin->id;
        $form->display('id', 'ID');
        $orgRole = AdminRole::where('name', 'org')->first();
        $orgUsers = User::where('user_type', $orgRole->id)
            ->get(['first_name', 'last_name', 'id'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                ];
            })
            ->pluck('full_name', 'id');

        $form->select('user_id', 'Admin')->options($orgUsers);
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $form->select('vsla_organisation_id', 'VSLA Organisation')->options(VslaOrganisation::where('id', $orgId)->pluck('name', 'id'));
            }
        } else {
            $form->select('vsla_organisation_id', 'VSLA Organisation')->options(VslaOrganisation::all()->pluck('name', 'id'));
        }

        $form->text('position', 'Position')->help('Optional: Enter the position of the admin in the organization');
        $form->text('region', 'Region')->help('Optional: Enter the region of assignment');
        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');

        $form->saving(function (Form $form) {
            $existingAllocation = OrgAllocation::where('user_id', $form->user_id)
                ->where('vsla_organisation_id', $form->vsla_organisation_id)
                ->where('id', '!=', $form->model()->id)
                ->exists();

            if ($existingAllocation) {
                admin_error("This admin is already assigned to the selected organization.");
                return back();
            } else {
                $adminUser = User::find($form->user_id);
                $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $adminUser->password = Hash::make($password);
                $adminUser->save();

                $org = VslaOrganisation::where('id', $form->vsla_organisation_id)->first();
                $platformLink = "https://digisave.m-omulimisa.com/";
                $message = "Hello {$adminUser->first_name} {$adminUser->last_name}, you have successfully been registered as {$org->name} organisation administrator. Your login details are: Phone Number: {$adminUser->phone_number}, Password: {$password}. Click here to access the platform: {$platformLink}";
                $email_info = [
                    "first_name" => $adminUser->first_name,
                    "last_name" => $adminUser->last_name,
                    "phone_number" => $adminUser->email,
                    "password" => $password,
                    "platformLink" => $platformLink,
                    "org" => $org->name,
                    "email" => 'dninsiima@m-omulimisa.com'
                ];

                $resp = null;
                try {
                    info('Send mail');
                    Mail::to($adminUser->email)->send(new SendMail($email_info));
                    info('After Send mail');
                    admin_toastr("Email sent successfully to {$adminUser->email}");
                    $resp = Utils::send_sms($adminUser->phone_number, $message);
                } catch (Exception $e) {
                    info($e->getMessage());
                    admin_error('Failed to send email because ' . $e->getMessage());
                    throw new Exception('Failed to send email because ' . $e->getMessage());
                }
            }
        });

        return $form;
    }
}
