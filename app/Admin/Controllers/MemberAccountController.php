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
        // $grid->disableCreateButton();

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

    protected function form()
{
    $form = new Form(new Transaction());

    // Group selection
    $form->select('sacco_id', __('Group'))
        ->options(
            Sacco::whereNotIn('status', ['deleted', 'inactive'])
                ->pluck('name', 'id')
        )
        ->rules('required');

    $form->select('source_user_id', __('Source User'))
        ->options(
            User::select('id', 'first_name', 'last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    return [$user->id => "{$user->first_name} {$user->last_name}"];
                })
        )
        ->rules('required');

    // Admin user selection
    $form->select('user_id', __('User'))
        ->options(
            User::where('user_type', 'Admin')
                ->select('id', 'first_name', 'last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    return [$user->id => "{$user->first_name} {$user->last_name}"];
                })
        )
        ->rules('required');

    // Amount entry
    $form->decimal('amount', __('Transaction Amount'))
        ->rules('required|numeric|not_in:0')
        ->help('Enter a positive amount to add or a negative amount to subtract.');

    // Display transaction type as SAVING but store as SHARE
    $form->hidden('type')->default('SHARE');
    $form->display('transaction_type', __('Transaction Type'))->default('SAVING');

    return $form;
}

    /**
     * Additional methods for fetching users based on the sacco
     */
    public function fetchSaccoUsers($saccoId)
    {
        return User::where('sacco_id', $saccoId)
                   ->where('user_type', '<>', 'Admin')
                   ->get(['id', 'first_name'])
                   ->pluck('first_name', 'id');
    }
}
