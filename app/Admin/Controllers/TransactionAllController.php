<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TransactionAllController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'All Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Transaction());

        // Create a filter
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Filter by sacco
            $saccos = \App\Models\Sacco::all();
            $filter->equal('sacco_id', 'Group')->select($saccos->pluck('name', 'id'));

            // // Sacco members for select
            // $sacco_members = \App\Models\User::where('sacco_id', \Encore\Admin\Facades\Admin::user()->sacco_id)
            //     ->whereNotIn('user_type', ['admin', 'org', '5'])
            //     ->get();

            // $filter->equal('source_user_id', 'Member Account')->select($sacco_members->pluck('name', 'id'));

            // Amount in range
            $filter->between('amount', 'Amount (UGX)');

            // Type in select
            $filter->equal('type', 'Transaction')->select(TRANSACTION_TYPES);

            // Date range
            $filter->between('created_at', 'Created')->date();
        });

        $grid->disableCreateButton();

        // Order by latest created date
        $grid->model()->orderBy('created_at', 'desc');

        // Filter transactions by users excluding specific user types
        // $grid->model()->whereIn('user_id', function ($query) {
        //     $query->select('id')
        //         ->from('users')
        //         ->whereNotIn('user_type', ['admin', 'org', '5']);
        // });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('sacco_id', __('Group'))
            ->display(function ($sacco_id) {
                $sacco = \App\Models\Sacco::find($sacco_id);
                if ($sacco == null) {
                    return "Unknown";
                }
                return $sacco->name;
            })->sortable();

        $grid->column('source_user_id', __('Member'))
            ->display(function ($user_id) {
                $user = \App\Models\User::find($user_id);
                if ($user == null) {
                    return "Unknown";
                }
                return $user->name;
            })->sortable();

        $grid->column('type', __('Transaction Type'))
            ->dot([
                'Share Purchase' => 'success',
                'Loan Disbursement' => 'warning',
                'Transfer' => 'info',
                'Send' => 'danger',
            ])
            ->sortable();

        $grid->column('amount', __('Amount (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable()
            ->totalRow(function ($amount) {
                return "<strong>Total: " . number_format($amount) . "</strong>";
            });

        $grid->column('description', __('Description'));

        $grid->column('details', __('Details'))->hide();

        $grid->column('created_at', __('Created'))->sortable();

        $grid->disableActions();
        $grid->disableBatchActions();

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
        $show = new Show(Transaction::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('user_id', __('User id'));
        $show->field('source_user_id', __('Source user id'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('type', __('Type'));
        $show->field('source_type', __('Source type'));
        $show->field('source_mobile_money_number', __('Source mobile money number'));
        $show->field('source_mobile_money_transaction_id', __('Source mobile money transaction id'));
        $show->field('source_bank_account_number', __('Source bank account number'));
        $show->field('source_bank_transaction_id', __('Source bank transaction id'));
        $show->field('desination_type', __('Desination type'));
        $show->field('desination_mobile_money_number', __('Desination mobile money number'));
        $show->field('desination_mobile_money_transaction_id', __('Desination mobile money transaction id'));
        $show->field('desination_bank_account_number', __('Desination bank account number'));
        $show->field('desination_bank_transaction_id', __('Desination bank transaction id'));
        $show->field('amount', __('Amount'));
        $show->field('description', __('Description'));
        $show->field('details', __('Details'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Transaction());

        $u = Admin::user();
        //only show sacco admin can create new transaction
        $sacco_members = \App\Models\User::where('sacco_id', \Encore\Admin\Facades\Admin::user()->sacco_id)->get();

        $form->select('user_id', __('Select Account'))->options($sacco_members->pluck('name', 'id'))
            ->rules('required');
        $form->hidden('source_user_id')->value($u->id);
        $form->hidden('sacco_id')->value($u->sacco_id);
        $form->radio('type', __('Tranasaction Type'))
            ->options([
                'Debit' => 'Debit (+)',
                'Credit' => 'Credit (-)',
            ]);

        $form->decimal('amount', __('Amount'))
            ->rules('required');

        $form->textarea('details', __('Details'))->rules('required');

        return $form;
    }
}
