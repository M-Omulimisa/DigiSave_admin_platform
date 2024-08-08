<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;

class GroupTransactionController extends AdminController
{
    protected $title = 'Transactions';

    public function index(Content $content)
    {
        // Get sacco_id from request
        $saccoId = request()->get('sacco_id');

        // Fetch the group (Sacco) name
        $sacco = Sacco::find($saccoId);
        $groupName = $sacco ? $this->sentenceCase($sacco->name) : 'Unknown Group';

        $title = "{$groupName} Transactions";

        return $content
            ->header($title)
            ->body($this->grid($saccoId));
    }

    /**
     * Convert a string to sentence case.
     *
     * @param string $string
     * @return string
     */
    protected function sentenceCase($string)
    {
        return ucfirst(strtolower($string));
    }

    protected function grid($saccoId)
    {
        $grid = new Grid(new Transaction());

        // Filter transactions by sacco_id if provided in the request
        if ($saccoId) {
            $grid->model()->where('sacco_id', $saccoId);
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
        $grid->column('created_at', __('Created At'))->display(function ($createdAt) {
            return \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i:s');
        })->sortable();
        // Add a column for viewing details
        $grid->column('details', __('Details'))->display(function () {
            return '<a href="' . url('transactions/' . $this->id) . '">View Details</a>';
        });

        // Add a column for editing transactions
        $grid->column('edit', __('Edit'))->display(function () {
            if ($this->type === 'SHARE') {
                return '<a href="' . url('transactions/' . $this->id . '/edit') . '">Edit</a>';
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
        $form = new Form(new Transaction());

        $form->display('id', __('ID'));
        $form->select('user_id', __('User'))
            ->options(\App\Models\User::all()->pluck('name', 'id'))
            ->rules('required');
        $form->display('type', __('Type'))->with(function ($value) {
            return $value === 'REGESTRATION' ? 'Registration' : $value;
        }); // Make type field non-editable
        $form->decimal('amount', __('Amount'))->rules('required|numeric|min:0');
        $form->textarea('description', __('Description'))->rules('required');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        // Adding JavaScript to update the description based on the amount
        $form->html('<script>
        document.addEventListener("DOMContentLoaded", function() {
            var amountField = document.querySelector("input[name=\'amount\']");
            var descriptionField = document.querySelector("textarea[name=\'description\']");
            var transactionTypeField = document.querySelector("div[data-value=\'type\']").innerText;

            amountField.addEventListener("input", function() {
                var userName = document.querySelector("select[name=\'user_id\'] option:checked").text;
                var amount = amountField.value;
                descriptionField.value = "Update of UGX " + amount + " on " + transactionTypeField + " for " + userName + " transaction.";
            });
        });
        </script>');

        // Handle the form saving event to update the description server-side
        $form->saving(function (Form $form) {
            $user = \App\Models\User::find($form->user_id);
            $form->description = "Update of UGX {$form->amount} on {$form->model()->type} for {$user->name} transaction.";
        });

        return $form;
    }
}
