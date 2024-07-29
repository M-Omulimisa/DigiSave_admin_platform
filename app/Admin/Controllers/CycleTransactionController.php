<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\Cycle;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;

class CycleTransactionController extends AdminController
{
    protected $title = 'Transactions';

    public function index(Content $content)
    {
        // Get sacco_id and cycle_id from request
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        // Fetch the cycle and group (Sacco) name
        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';

        $title = "{$groupName} - Cycle {$cycle->name} Transactions";

        return $content
            ->header($title)
            ->body($this->grid($cycleId, $saccoId));
    }

    protected function grid($cycleId, $saccoId)
    {
        $grid = new Grid(new Transaction());

        // Filter transactions by cycle_id and sacco_id if provided in the request
        if ($cycleId && $saccoId) {
            $grid->model()->where('cycle_id', $cycleId)->where('sacco_id', $saccoId);
        }

        $grid->column('id', __('ID'))->sortable();
        $grid->column('user.name', __('Account'))->sortable();
        $grid->column('type', __('Type'))->display(function ($type) {
            return $type === 'REGESTRATION' ? 'REGISTRATION' : $type;
        })->sortable();
        $grid->column('amount', __('Amount (UGX)'))->display(function ($amount) {
            return number_format($amount, 2, '.', ',');
        })->sortable();
        $grid->column('description', __('Description'));
        $grid->column('created_at', __('Created At'))->sortable();

        // Add a column for viewing details
        $grid->column('details', __('Details'))->display(function () {
            return '<a href="' . url('admin/transactions/' . $this->id) . '">View Details</a>';
        });

        // Add a column for editing transactions
        $grid->column('edit', __('Edit'))->display(function () {
            if ($this->type === 'SHARE') {
                return '<a href="' . url('admin/transactions/' . $this->id . '/edit') . '">Edit</a>';
            } else {
                return '<span style="color: grey;">Edit</span>';
            }
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user.name', 'User Name');
            $filter->equal('type', 'Type')->select(['SHARE' => 'SHARE', 'Send' => 'Send', 'Receive' => 'Receive', 'REGESTRATION' => 'Registration']);
            $filter->between('amount', 'Amount');
            $filter->between('created_at', 'Created At')->datetime();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Transaction::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('user.name', __('User Name'));
        $show->field('type', __('Type'))->as(function ($type) {
            return $type === 'REGESTRATION' ? 'Registration' : $type;
        });
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return number_format($amount, 2, '.', ',') . ' UGX';
        });
        $show->field('description', __('Description'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
{
    $saccoId = request()->get('sacco_id');
    $cycleId = request()->get('cycle_id');

    $form = new Form(new Transaction());

    $form->display('id', __('ID'));

    // Filter users by sacco_id
    $form->select('user_id', __('User'))
        ->options(User::where('sacco_id', $saccoId)->pluck('name', 'id'))
        ->rules('required');

    // Allow transaction type selection
    $form->select('type', __('Type'))
        ->options(Transaction::select('type')->distinct()->pluck('type', 'type')->toArray())
        ->rules('required');

    // Allow user to enter the created_at timestamp
    $form->datetime('created_at', __('Created At'))->default(date('Y-m-d H:i:s'))->rules('required|date');

    $form->decimal('amount', __('Amount'))->rules('required|numeric|min:0');
    $form->textarea('description', __('Description'));

    // Adding JavaScript to update the description based on the amount
    $form->html('<script>
    document.addEventListener("DOMContentLoaded", function() {
        var amountField = document.querySelector("input[name=\'amount\']");
        var descriptionField = document.querySelector("textarea[name=\'description\']");
        var transactionTypeField = document.querySelector("select[name=\'type\'] option:checked").text;

        amountField.addEventListener("input", function() {
            var userName = document.querySelector("select[name=\'user_id\'] option:checked").text;
            var amount = amountField.value;
            descriptionField.value = "Update of UGX " + amount + " on " + transactionTypeField + " for " + userName + " transaction.";
        });

        // Update description on type change
        document.querySelector("select[name=\'type\']").addEventListener("change", function() {
            transactionTypeField = this.options[this.selectedIndex].text;
            var amount = amountField.value;
            var userName = document.querySelector("select[name=\'user_id\'] option:checked").text;
            descriptionField.value = "Update of UGX " + amount + " on " + transactionTypeField + " for " + userName + " transaction.";
        });
    });
    </script>');

    // Handle the form saving event to update the description server-side
    $form->saving(function (Form $form) use ($saccoId, $cycleId) {
        $user = User::find($form->user_id);
        $form->description = "Update of UGX {$form->amount} on {$form->type} for {$user->name} transaction.";
        $form->model()->sacco_id = $saccoId;
        $form->model()->cycle_id = $cycleId;
    });

    return $form;
}


}
