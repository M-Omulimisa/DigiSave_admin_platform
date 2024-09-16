<?php

namespace App\Admin\Controllers;

use App\Models\AuditLog;
use App\Models\AuditObserverModel;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;

class AuditLogController extends AdminController
{
    protected $title = 'Audit Logs';

    // Define the grid layout for viewing the logs
    protected function grid()
    {
        $grid = new Grid(new AuditLog());

        // $grid->column('admin', __('Admin'))->display(function () {
        //     return $this->first_name . ' ' . $this->last_name;
        // })->sortable();

        $grid->column('first_name', __('First Name'))->sortable();
        $grid->column('last_name', __('Last Name'))->sortable();

        $grid->column('action', __('Action'))
            ->sortable()
            ->filter([
                'created' => 'Created',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
                'restored' => 'Restored',
                'force_deleted' => 'Force Deleted',
            ]);

        $grid->column('model', __('Model'))->sortable();

        $grid->column('model_id', __('Model ID'))->sortable();

        $grid->column('changes', __('Changes'))->display(function ($changes) {
            $changesArray = json_decode($changes, true);

            if (is_array($changesArray)) {
                return implode(', ', array_map(function ($key, $value) {
                    return "$key: $value";
                }, array_keys($changesArray), $changesArray));
            }

            return $changes;
        })->limit(50);

        $grid->column('created_at', __('Date'))
            ->display(function ($createdAt) {
                return date('d M Y H:i:s', strtotime($createdAt));
            })->sortable();

        $grid->disableCreateButton();
        $grid->disableBatchActions();
        return $grid;
    }

    // Define the detail layout for viewing individual logs
    protected function detail($id)
    {
        $show = new Show(AuditLog::findOrFail($id));

        $show->field('user_id', __('Admin'))->as(function ($userId) {
            $admin = \Encore\Admin\Auth\Database\Administrator::find($userId);
            return $admin ? $admin->name : 'Unknown Admin';
        });

        $show->field('action', __('Action'));

        $show->field('model', __('Model'));

        $show->field('model_id', __('Model ID'));

        $show->field('changes', __('Changes'))->as(function ($changes) {
            // Decode the JSON data
            $changesArray = json_decode($changes, true);

            // Pretty print the JSON if it's a valid array
            if (is_array($changesArray)) {
                return json_encode($changesArray, JSON_PRETTY_PRINT);
            }

            return $changes; // Fallback to raw string if it's not JSON
        });

        $show->field('created_at', __('Date'));

        return $show;
    }

    // Optionally define a form for editing logs (usually not needed for audit logs)
    protected function form()
    {
        $form = new Form(new AuditLog());

        $form->text('user_id', __('User ID'));
        $form->text('action', __('Action'));
        $form->text('model', __('Model'));
        $form->text('model_id', __('Model ID'));
        $form->textarea('changes', __('Changes'));

        return $form;
    }
}
