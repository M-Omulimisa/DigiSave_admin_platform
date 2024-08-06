<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use App\Models\User;
use Encore\Admin\Facades\Admin;

class SummaryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User Summary';

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

        // Query directly for summaries using correct field names
        $grid->column('Groups')->display(function () {
            return User::where('user_type', 'Admin')->count();
        });

        $grid->column('Users')->display(function () {
            return User::where('user_type', '<>', 'Admin')->count();
        });

        $grid->column('Male Users')->display(function () {
            return User::where('sex', 'male')
                       ->where('user_type', '<>', 'Admin')
                       ->count();
        });

        $grid->column('Female Users')->display(function () {
            return User::where('sex', 'female')
                       ->where('user_type', '<>', 'Admin')
                       ->count();
        });
        ;
        $grid->column('Users with Disabilities')->display(function () {
            return User::where('pwd', 'yes')->count();
        });

        $grid->column('Youths Count')->display(function () {
            // Assuming the `dob` column stores the date of birth
            return User::whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) <= 35')
                       ->where('user_type', '<>', 'Admin')
                       ->count();
        });

        return $grid;
    }
}
