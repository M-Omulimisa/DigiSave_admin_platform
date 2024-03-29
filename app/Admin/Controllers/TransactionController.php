<?php

namespace App\Admin\Controllers;

use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Transaction());
        $u = Admin::user();
        if (!$u->isRole('admin')) {
            // if (!$u->isRole('sacco')) {
                $grid->disableCreateButton();
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                $grid->disableFilter();

        }
        $grid->disableCreateButton();
        //create a filter
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            //sacco members for select
            $sacco_members = \App\Models\User::where('sacco_id', \Encore\Admin\Facades\Admin::user()->sacco_id)->get();
            //sacco members for select
            $filter->equal('user_id', 'Account')->select($sacco_members->pluck('name', 'id'));

            //amount in range
            $filter->between('amount', 'Amount (UGX)');
            //type in select
            $filter->equal('type', 'Transaction')->select(TRANSACTION_TYPES);
            //date range
            $filter->between('created_at', 'Created')->date();
        });
        // $grid->column('id', __('ID'))
        //     ->sortable();
        // $grid->column('created_at', __('DATE'))
            // ->sortable()
            // ->display(function ($x) {
            //     return Utils::my_date_time($x);
            // });


        $grid->column('user_id', __('Account'))
            ->display(function ($user_id) {
                $user = \App\Models\User::find($user_id);
                if ($user == null) {
                    return "Unknown";
                }
                return $user->name;
            })->sortable();
        $grid->column('source_user_id', __('Source'))
            ->display(function ($user_id) {
                $user = \App\Models\User::find($user_id);
                if ($user == null) {
                    return "Unknown";
                }
                return $user->name;
            })->sortable()
            ->hide();$grid->column('type', __('Transaction Type'))
            ->sortable()
            ->display(function ($type) {
                // Correct spelling if the type is "regestration"
                if (strtolower($type) === 'regestration') {
                    $type = 'Registration';
                }
                return ucwords(strtolower($type));
            });
        $grid->column('amount', __('Amount (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable()
            ->totalRow(function ($amount) {
                return "<strong>Total: " . number_format($amount) . "</strong>";
            });
        $grid->column('description', __('Description'));

        $grid->column('details', __('Details'))->hide();
        $grid->column('created_at', __('Created'))->sortable()
        ->display(function ($createdAt) {
            return date('Y-m-d', strtotime($createdAt));
        });
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

// In the form method:
    // protected function form()
    // {
    //     $form = new Form(new Transaction());

    //     // Retrieve the list of SACCOs
    //     $saccos = Sacco::pluck('name', 'id');

    //     $form->select('sacco_id', __('Select SACCO'))->options($saccos)->rules('required');

    //     $form->select('user_id', __('Select Account'))->options(function ($saccoId) {
    //         // Fetch users based on the selected SACCO
    //         $users = User::where('sacco_id', $saccoId)->pluck('name', 'id');
    //         return $users;
    //     })->rules('required');

    //     $form->radio('type', __('Transaction Type'))
    //         ->options([
    //             'SAVING' => 'Debit (+)',
    //             'LOAN' => 'Credit (-)',
    //         ]);

    //     $form->decimal('amount', __('Amount'))->rules('required');

    //     $form->textarea('details', __('Details'))->rules('required');

    //     return $form;
    // }

}
