<?php

namespace App\Admin\Controllers;

use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use App\Models\MemberPosition;
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

    if (!$admin->isRole('admin')) {
        $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
        if ($orgAllocation) {
            $orgId = $orgAllocation->vsla_organisation_id;
            $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
            $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
            $grid->model()->whereIn('id', $saccoIds)
                          ->whereHas('users', function ($query) {
                              $query->whereHas('position', function ($query) {
                                  $query->where('name', 'Chairperson');
                              })->whereNotNull('phone_number')
                                ->whereNotNull('name');
                          })
                          ->orderBy('created_at', $sortOrder);
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
        }
    } else {
        // For admins, display all records ordered by created_at
        $grid->model()->whereHas('users', function ($query) {
                              $query->whereHas('position', function ($query) {
                                  $query->where('name', 'Chairperson');
                              })->whereNotNull('phone_number')
                                ->whereNotNull('name');
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

    $grid->column('created_at', __('Created At'))
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
                    <li><a href="' . url()->current() . '?_sort=asc">Ascending</a></li>
                    <li><a href="' . url()->current() . '?_sort=desc">Descending</a></li>
                </ul>
            </div>
        ');
    });

    return $grid;
}

    // protected function grid()
    // {
    //     $grid = new Grid(new Sacco());

    //     $admin = Admin::user();
    //     $adminId = $admin->id;

    //     // Default sort order
    //     $sortOrder = request()->get('_sort', 'desc');
    //     if (!in_array($sortOrder, ['asc', 'desc'])) {
    //         $sortOrder = 'desc';
    //     }

    //     if (!$admin->isRole('admin')) {
    //         $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
    //         if ($orgAllocation) {
    //             $orgId = $orgAllocation->vsla_organisation_id;
    //             $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();
    //             $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
    //             $grid->model()->whereIn('id', $saccoIds)->orderBy('created_at', $sortOrder);
    //             $grid->disableCreateButton();
    //             $grid->actions(function (Grid\Displayers\Actions $actions) {
    //                 $actions->disableDelete();
    //             });
    //         }
    //     } else {
    //         // For admins, display all records ordered by created_at
    //         $grid->model()->orderBy('created_at', $sortOrder);
    //     }

    //     $grid->showExportBtn();
    //     $grid->disableBatchActions();
    //     $grid->quickSearch('name')->placeholder('Search by name');

    //     $grid->column('name', __('Name'))->sortable()->display(function ($name) {
    //         return ucwords(strtolower($name));
    //     });

    //     $grid->column('phone_number', __('Phone Number'))
    //         ->sortable()
    //         ->display(function () {
    //             $chairperson = \App\Models\User::where('sacco_id', $this->id)
    //                 ->whereHas('position', function ($query) {
    //                     $query->where('name', 'Chairperson');
    //                 })
    //                 ->first();

    //             return $chairperson ? $chairperson->phone_number : '';
    //         });

    //     $grid->column('share_price', __('Share (UGX)'))
    //         ->display(function ($price) {
    //             return number_format($price);
    //         })->sortable();

    //     $grid->column('physical_address', __('Physical Address'))->sortable()->display(function ($address) {
    //         return ucwords(strtolower($address));
    //     });

    //     $grid->column('created_at', __('Created At'))
    //         ->display(function ($date) {
    //             return date('d M Y', strtotime($date));
    //         })->sortable();

    //     $grid->column('chairperson_name', __('Chairperson Name'))
    //         ->sortable()
    //         ->display(function () {
    //             $chairperson = \App\Models\User::where('sacco_id', $this->id)
    //                 ->whereHas('position', function ($query) {
    //                     $query->where('name', 'Chairperson');
    //                 })
    //                 ->first();

    //             return $chairperson ? ucwords(strtolower($chairperson->name)) : '';
    //         });

    //     // Adding search filters
    //     $grid->filter(function ($filter) {
    //         $filter->disableIdFilter();

    //         $filter->like('name', 'Name');
    //         $filter->like('phone_number', 'Phone Number');
    //         $filter->like('physical_address', 'Physical Address');
    //     });

    //     // Adding custom dropdown for sorting
    //     $grid->tools(function ($tools) {
    //         $tools->append('
    //             <div class="btn-group pull-right" style="margin-right: 10px; margin-left: 10px;">
    //                 <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
    //                     Sort by Established <span class="caret"></span>
    //                 </button>
    //                 <ul class="dropdown-menu" role="menu">
    //                     <li><a href="' . url()->current() . '?_sort=asc">Ascending</a></li>
    //                     <li><a href="' . url()->current() . '?_sort=desc">Descending</a></li>
    //                 </ul>
    //             </div>
    //         ');
    //     });

    //     return $grid;
    // }

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
        } else {
            $form->tab('Sacco Details', function ($form) {
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
            })->tab('Group Leader Details', function ($form) {
                $form->text('leader_first_name', 'First Name')->rules('required');
                $form->text('leader_last_name', 'Last Name')->rules('required');
                $form->text('leader_national_id', 'National ID Number');
                $form->text('leader_phone_number', 'Phone Number')->rules('required');
                $form->select('leader_gender', 'Gender')->options(['Male' => 'Male', 'Female' => 'Female'])->rules('required');
                $form->date('leader_dob', 'Date of Birth')->rules('required');
                $form->select('leader_pwd', 'Is the member a PWD?')->options(['Yes' => 'Yes', 'No'])->rules('required');

                $form->select('leader_position_id', 'Leader Position')->options(function () {
                    return MemberPosition::pluck('name', 'id');
                })->rules('required');
            });

            $form->editing(function (Form $form) {
                // Generate phone number in the background
                $initialPhoneNumber = '0701399995'; // Dummy initial phone number to generate from
                $generatedPhoneNumber = substr($initialPhoneNumber, 3, 3) . date('Y') . strtoupper(Str::random(2));
                $form->model()->phone_number = $generatedPhoneNumber;
            });

            $form->saving(function (Form $form) {
                // Ensure the generated phone number is saved
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
                }

                // Generate 8-digit code combining phone number digits, current year, and random letters
                $code = substr($phone_number, 3, 3) . date('Y') . strtoupper(Str::random(2));
                $user->username = $code;
                $user->phone_number = $phone_number;
                $user->name = $name;
                $user->password = bcrypt($code);

                $user->save();

                $form->administrator_id = $user->id;
            });

            $form->saved(function (Form $form) {
                // Create Group Leader
                \App\Models\User::create([
                    'first_name' => $form->model()->leader_first_name,
                    'last_name' => $form->model()->leader_last_name,
                    'national_id' => $form->model()->leader_national_id,
                    'phone_number' => $form->model()->leader_phone_number,
                    'gender' => $form->model()->leader_gender,
                    'dob' => $form->model()->leader_dob,
                    'pwd' => $form->model()->leader_pwd,
                    'position_id' => $form->model()->leader_position_id,
                    'sacco_id' => $form->model()->id,
                ]);

                // Create default positions
                $positions = ['Chairperson', 'Secretary', 'Treasurer'];
                foreach ($positions as $positionName) {
                    MemberPosition::create([
                        'name' => $positionName,
                        'sacco_id' => $form->model()->id,
                    ]);
                }
            });

            // Remove the leader details from the Sacco model
            $form->ignore(['leader_first_name', 'leader_last_name', 'leader_national_id', 'leader_phone_number', 'leader_gender', 'leader_dob', 'leader_pwd', 'leader_position_id']);

            $form->hidden('administrator_id');
        }

        return $form;
    }
}
