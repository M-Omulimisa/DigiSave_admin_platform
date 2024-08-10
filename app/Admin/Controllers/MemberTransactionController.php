<?php

namespace App\Admin\Controllers;

use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MemberTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Member Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Transaction());

        $user = Admin::user();

        $grid->disableCreateButton();

        // Create a filter
        $grid->filter(function ($filter) use ($user) {
            $filter->disableIdFilter();

            // Sacco members for select
            $saccoMembers = User::where('sacco_id', $user->sacco_id)->get();
            $filter->equal('user_id', 'Account')->select($saccoMembers->pluck('full_name', 'id'));

            // Amount in range
            $filter->between('amount', 'Amount (UGX)');

            // Transaction type
            $filter->equal('type', 'Transaction Type')->select(TRANSACTION_TYPES);

            // Date range
            $filter->between('created_at', 'Created')->date();
        });

        $grid->column('sacco_id', __('Group'))
            ->display(function ($saccoId) {
                $sacco = Sacco::find($saccoId);
                return $sacco ? $sacco->name : 'Unknown';
            })->sortable();

        $grid->column('source_user_id', __('Source User'))
            ->display(function ($sourceUserId) {
                $user = User::find($sourceUserId);
                return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
            })->sortable();

        $grid->column('user_id', __('User Name'))
            ->display(function ($userId) {
                $user = User::find($userId);
                return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
            })->sortable();

        $grid->column('type', __('Transaction Type'))
            ->sortable()
            ->display(function ($type) {
                return ucwords(strtolower($type));
            });

        $grid->column('amount', __('Amount (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable()
            ->totalRow(function ($amount) {
                return "<strong>Total: " . number_format($amount) . "</strong>";
            });

        // $grid->disableActions();
        // $grid->disableBatchActions();

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

        $saccos = Sacco::pluck('name', 'id');

        // SACCO selection
        $form->display('sacco_id', __('Group'))->with(function ($saccoId) {
            $sacco = Sacco::find($saccoId);
            return $sacco ? $sacco->name : 'Unknown';
        });

        // User selection
        $form->display('user_id', __('User Name'))->with(function ($userId) {
            $user = User::find($userId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
        });

        // Source user selection
        $form->display('source_user_id', __('Source User'))->with(function ($sourceUserId) {
            $user = User::find($sourceUserId);
            return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
        });

        // Transaction type selection
        $form->display('type', __('Transaction Type'))->with(function ($type) {
            return ucwords(strtolower($type));
        });

        // Amount input (editable)
        $form->decimal('amount', __('Amount'))->rules('required');

        // Details input (optional for edit)
        $form->textarea('details', __('Details'))->rules('nullable');

        return $form;
    }
}
