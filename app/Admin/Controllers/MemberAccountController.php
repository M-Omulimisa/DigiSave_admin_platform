<?php

namespace App\Admin\Controllers;

use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class MemberAccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Member Accounts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Transaction());

        // Aggregate shares by source_user_id and sacco_id
        $grid->model()
            ->select(
                'transactions.source_user_id',
                'transactions.sacco_id',
                DB::raw('SUM(transactions.amount) as total_share')
            )
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where(function ($query) {
                $query->whereNull('users.user_type')
                      ->orWhere('users.user_type', '!=', 'Admin');
            })
            ->join('saccos', 'transactions.sacco_id', '=', 'saccos.id')
            ->where('transactions.type', 'SHARE')
            ->groupBy('transactions.source_user_id', 'transactions.sacco_id');

        // Quick search by group name (Sacco name)
        $grid->quickSearch('saccos.name')->placeholder('Search by group name');

        // Display the group name (Sacco name)
        $grid->column('sacco_id', __('Group'))
            ->display(function () {
                $sacco = Sacco::find($this->sacco_id);
                return $sacco ? $sacco->name : 'Unknown';
            })->sortable();

        // Display the source user full name
        $grid->column('source_user_id', __('Source User'))
            ->display(function () {
                $user = User::find($this->source_user_id);
                return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
            })->sortable();

        // Display the sum of share amount
        $grid->column('total_share', __('Total Share Amount'))
            ->display(function ($value) {
                return number_format($value);
            })
            ->sortable();

        // Disable create button as this grid is for viewing purposes
        $grid->disableCreateButton();

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

        // SACCO selection (non-editable display)
        $form->display('sacco_id', __('Group'))->with(function ($saccoId) {
            $sacco = Sacco::find($saccoId);
            return $sacco ? $sacco->name : 'Unknown';
        });

        // Source user selection (non-editable display)
        $form->display('source_user_id', __('Source User'))->with(function ($sourceUserId) {
            $user = User::find($sourceUserId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
        });

        // Total share amount (editable)
        $form->decimal('new_share_amount', __('New Total Share Amount'))
            ->rules('required|numeric|min:0')
            ->help('Specify the new total share amount. A negative transaction will be created to adjust the current amount.');

        $form->saved(function (Form $form) {
            $transaction = $form->model();
            $currentTotalShare = $form->model()->total_share;
            $newTotalShare = $form->new_share_amount;
            $difference = $newTotalShare - $currentTotalShare;

            if ($difference < 0) {
                DB::transaction(function () use ($transaction, $difference) {
                    // Create a new negative transaction to deduct the amount
                    Transaction::create([
                        'sacco_id' => $transaction->sacco_id,
                        'source_user_id' => $transaction->source_user_id,
                        'user_id' => $transaction->source_user_id,
                        'type' => 'Share',
                        'amount' => $difference, // negative value to deduct
                        'description' => 'Adjustment of total share amount',
                        'details' => 'Automated adjustment to update share balance',
                    ]);
                });
            }
        });

        return $form;
    }
}
