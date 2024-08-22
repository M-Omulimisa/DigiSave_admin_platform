<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ActivateSacco;
use App\Models\Sacco;
use App\Models\User; // Make sure to include the User model
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Actions\RowAction;

class SaccoDeletedActiveController extends AdminController
{
    protected $title = 'VSLA Groups (Deleted and Inactive)';

    protected function grid()
    {
        $grid = new Grid(new Sacco());

        // Fetch only saccos with status 'deleted' or 'inactive'
        $grid->model()
            ->whereIn('status', ['deleted', 'inactive'])
            ->orderBy('created_at', 'desc');

        $grid->column('name', __('Name'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('status', __('Status'))->sortable()->display(function ($status) {
            return ucfirst($status);
        });

        // Column for Chairperson Name
        $grid->column('chairperson_name', __('Chairperson Name'))
            ->sortable()
            ->display(function () {
                $user = User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $user ? ucwords(strtolower($user->name)) : '';
            });

        // Separate column for Chairperson Phone Number
        $grid->column('chairperson_phone_number', __('Chairperson Phone Number'))
            ->sortable()
            ->display(function () {
                $user = User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->where('name', 'Chairperson');
                    })
                    ->first();

                return $user ? $user->phone_number : '';
            });

        // New column for Admin Phone Number
        $grid->column('admin_phone_number', __('Passcode'))
            ->sortable()
            ->display(function () {
                $adminUser = User::where('sacco_id', $this->id)
                    ->where('user_type', 'Admin')
                    ->first();

                return $adminUser ? $adminUser->phone_number : '';
            });

        $grid->column('updated_at', __('Updated At'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();

        // Add a custom action for activating the Sacco
        $grid->actions(function ($actions) {
            $actions->add(new ActivateSacco);
        });

        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->disableBatchActions();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Sacco::findOrFail($id));

        $show->field('name', __('Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });

        $show->field('phone_number', __('Phone Number'));
        $show->field('physical_address', __('Physical Address'))->as(function ($address) {
            return ucwords(strtolower($address));
        });

        $show->field('status', __('Status'))->as(function ($status) {
            return ucfirst($status);
        });

        $show->field('created_at', __('Created At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Sacco());

        $form->text('name', __('Name'))->rules('required');
        $form->text('phone_number', __('Phone Number'))->rules('required');
        $form->text('physical_address', __('Physical Address'))->rules('required');
        $form->select('status', __('Status'))->options([
            'inactive' => 'Inactive',
            'deleted' => 'Deleted',
        ])->rules('required');
        $form->datetime('created_at', __('Created At'))->rules('required');

        $form->saving(function (Form $form) {
            // Additional logic before saving if needed
        });

        return $form;
    }
}
