<?php

namespace App\Admin\Controllers;

use App\Models\MemberPosition;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;

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

        // Add a condition to exclude Saccos with status "deleted"
        $grid->model()->where('status', '!=', 'deleted');

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->whereIn('id', $saccoIds)
                              ->where(function ($query) {
                                  $query->whereHas('users', function ($query) {
                                      $query->whereHas('position', function ($query) {
                                          $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                                      })->whereNotNull('phone_number')
                                        ->whereNotNull('name');
                                  });
                              })
                              ->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()->where(function ($query) {
                                $query->whereHas('users', function ($query) {
                                    $query->whereHas('position', function ($query) {
                                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                                    })->whereNotNull('phone_number')
                                      ->whereNotNull('name');
                                });
                            })
                          ->orderBy('created_at', $sortOrder);
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
                $user = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                    ->first();

                return $user ? $user->phone_number : '';
            });

        $grid->column('share_price', __('Share (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable();

        $grid->column('physical_address', __('Physical Address'))->sortable()->display(function ($address) {
            return ucwords(strtolower($address));
        });

        $grid->column('created_at', __('Created At'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();

        $grid->column('chairperson_name', __('Chairperson Name'))
            ->sortable()
            ->display(function () {
                $user = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                    ->first();

                return $user ? ucwords(strtolower($user->name)) : '';
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
        $show = new Show(Sacco::findOrFail($id));

        $show->field('name', __('Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });

        $show->field('phone_number', __('Phone Number'));

        $show->field('share_price', __('Share (UGX)'))->as(function ($price) {
            return number_format($price);
        });

        $show->field('physical_address', __('Physical Address'))->as(function ($address) {
            return ucwords(strtolower($address));
        });

        $show->field('created_at', __('Created At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });

        $show->field('chairperson_name', __('Chairperson Name'))->as(function () {
            $chairperson = \App\Models\User::where('sacco_id', $this->id)
                ->whereHas('position', function ($query) {
                    $query->where('name', 'Chairperson');
                })
                ->first();

            return $chairperson ? ucwords(strtolower($chairperson->name)) : '';
        });

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
        }

        // Sacco Details
        $form->text('name', __('Name'))->rules('required');
        $form->decimal('share_price', __('Share Price'))
            ->help('UGX')
            ->rules('required|numeric|min:0');
        // Hide phone number field, generate it in the background
        $form->hidden('phone_number');
        $form->text('email_address', __('Email Address'));
        $form->text('physical_address', __('Physical Address'));
        $form->datetime('created_at', __('Establishment Date'))->rules('required');
        $form->image('logo', __('VSLA Logo'));

        // Add location fields
        $form->text('district', __('District'))->rules('required');
        $form->text('subcounty', __('Subcounty'))->rules('required');
        $form->text('parish', __('Parish'))->rules('required');
        $form->text('village', __('Village'))->rules('required');

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                // Generate phone number in the background
                if (!$form->phone_number) {
                    $initialPhoneNumber = '0701399995'; // Dummy initial phone number to generate from
                    $form->phone_number = substr($initialPhoneNumber, 3, 3) . date('Y') . strtoupper(Str::random(2));
                }

                // Create new administrator
                $user = new Administrator();
                $name = $form->name;  // Using Sacco name for admin name
                $phone_number = $form->phone_number;
                $x = explode(' ', $name);

                if (isset($x[0]) && isset($x[1])) {
                    $user->first_name = $x[0];
                    $user->last_name = $x[1];
                } else {
                    $user->first_name = $name;
                    $user->last_name = '';
                }

                // Generate 8-digit code combining phone number digits, current year, and random letters
                $code = substr($phone_number, 3, 3) . date('Y') . strtoupper(Str::random(2));
                $user->username = $code;
                $user->phone_number = $phone_number;
                $user->name = $name;
                $user->password = bcrypt($code);

                $user->save();

                $form->administrator_id = $user->id;
            } else {
                // For editing, ensure administrator_id is not null
                if (!$form->administrator_id) {
                    $sacco = Sacco::find($form->model()->id);
                    $form->administrator_id = $sacco->administrator_id;
                }
            }
        });

        $form->saved(function (Form $form) {
            if ($form->isCreating()) {
                // Manually save Sacco with administrator ID
                $sacco = new Sacco();
                $sacco->name = $form->name;
                $sacco->share_price = $form->share_price;
                $sacco->phone_number = $form->phone_number;
                $sacco->email_address = $form->email_address;
                $sacco->physical_address = $form->physical_address;
                $sacco->created_at = $form->created_at;
                $sacco->district = $form->district;
                $sacco->subcounty = $form->subcounty;
                $sacco->parish = $form->parish;
                $sacco->village = $form->village;
                $sacco->administrator_id = $form->administrator_id;
                $sacco->save();

                // Create default positions
                $positions = ['Chairperson', 'Secretary', 'Treasurer'];
                foreach ($positions as $positionName) {
                    MemberPosition::create([
                        'name' => $positionName,
                        'sacco_id' => $sacco->id,
                    ]);
                }

                // Display success message
                admin_success('Success', 'Group ' . $sacco->name . ' created successfully');
            } else {
                // Update existing Sacco
                $sacco = Sacco::find($form->model()->id);
                $sacco->name = $form->name;
                $sacco->share_price = $form->share_price;
                $sacco->phone_number = $form->phone_number;
                $sacco->email_address = $form->email_address;
                $sacco->physical_address = $form->physical_address;
                $sacco->created_at = $form->created_at;
                $sacco->district = $form->district;
                $sacco->subcounty = $form->subcounty;
                $sacco->parish = $form->parish;
                $sacco->village = $form->village;
                $sacco->administrator_id = $form->administrator_id;
                $sacco->save();

                // Display success message
                admin_success('Success', 'Group ' . $sacco->name . ' updated successfully');
            }
        });

        $form->hidden('administrator_id');

        return $form;
    }
}
?>
