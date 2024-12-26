<?php

namespace App\Admin\Controllers;

use App\Models\LoanRepayment;
use App\Models\CreditLoan;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class LoanRepaymentController extends AdminController
{
    protected $title = 'Loan Repayments';

    protected function grid()
    {
        $grid = new Grid(new LoanRepayment());

        // Only show repayments for disbursed loans
        $grid->model()->whereHas('loan', function ($query) {
            $query->where('disbursement_status', 'disbursed');
        });

        $grid->filter(function($filter) {
            $filter->disableIdFilter();

            // Filter by loan ID
            $filter->equal('credit_loan_id', 'Loan ID');

            // Filter by status
            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'failed' => 'Failed'
            ]);

            // Date range filter
            $filter->between('payment_date', 'Payment Date')->datetime();
        });

        // $grid->column('id', __('ID'))->sortable();

        // Display Sacco Name - Loan ID
        $grid->column('loan.id', __('Loan'))->display(function ($loanId) {
            $url = admin_url('credit-loans/' . $this->credit_loan_id);
            return "<a href='{$url}' target='_blank'>{$this->loan->sacco->name} - {$loanId}</a>";
        })->sortable();

        $grid->column('amount_paid', __('Amount Paid'))->display(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        })->sortable();

        // $grid->column('principal_paid', __('Principal'))->display(function ($value) {
        //     return 'UGX ' . number_format($value, 0, '.', ',');
        // });

        // $grid->column('interest_paid', __('Interest'))->display(function ($value) {
        //     return 'UGX ' . number_format($value, 0, '.', ',');
        // });

        $grid->column('remaining_balance', __('Remaining'))->display(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        })->sortable();

        $grid->column('payment_method', __('Payment Method'));
        $grid->column('transaction_reference', __('Reference'));

        $grid->column('status', __('Status'))->display(function ($status) {
            $color = $status === 'confirmed' ? 'green' : ($status === 'failed' ? 'red' : 'orange');
            return "<button style='background-color: {$color}; color: white; padding: 5px 10px; border: none; border-radius: 5px;'>{$status}</button>";
        })->sortable();

        $grid->column('payment_date', __('Payment Date'))->display(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        })->sortable();

        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        })->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(LoanRepayment::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('loan.id', __('Loan'))->as(function ($loanId) {
            return $this->loan->sacco->name . ' - ' . $loanId;
        });
        $show->field('amount_paid', __('Amount Paid'))->as(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        });
        $show->field('principal_paid', __('Principal Paid'))->as(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        });
        $show->field('interest_paid', __('Interest Paid'))->as(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        });
        $show->field('remaining_balance', __('Remaining Balance'))->as(function ($value) {
            return 'UGX ' . number_format($value, 0, '.', ',');
        });
        $show->field('payment_method', __('Payment Method'));
        $show->field('transaction_reference', __('Transaction Reference'));
        $show->field('payment_proof', __('Payment Proof'));
        $show->field('notes', __('Notes'));
        $show->field('received_by', __('Received By'));
        $show->field('status', __('Status'));
        $show->field('payment_date', __('Payment Date'))->as(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        });
        $show->field('created_at', __('Created At'))->as(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        });
        $show->field('updated_at', __('Updated At'))->as(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        });

        return $show;
    }

    // In LoanRepaymentController.php
protected function form()
{
    $form = new Form(new LoanRepayment());

    // Format loans as "Sacco Name - Loan ID"
    $disbursedLoans = CreditLoan::where('disbursement_status', 'disbursed')
        ->with('sacco')
        ->get()
        ->mapWithKeys(function ($loan) {
            return [$loan->id => $loan->sacco->name . ' - ' . $loan->id];
        });

    $form->select('credit_loan_id', __('Select Loan'))
        ->options($disbursedLoans)
        ->required();

    // Only show total amount field
    $form->decimal('amount_paid', __('Amount Paid'))->required();

    // Hide these fields as they'll be calculated automatically
    $form->hidden('principal_paid');
    $form->hidden('interest_paid');
    $form->hidden('remaining_balance');

    $form->select('payment_method', __('Payment Method'))->options([
        'bank' => 'Bank Transfer',
        'airtel' => 'Airtel Money',
        'mtn' => 'MTN Mobile Money'
    ])->required();

    $form->text('transaction_reference', __('Transaction Reference'))->required();
    $form->file('payment_proof', __('Payment Proof'));
    $form->textarea('notes', __('Notes'));

    $form->text('received_by', __('Received By'))
        ->default(Admin::user()->name)
        ->readonly();

    $form->select('status', __('Status'))->options([
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'failed' => 'Failed'
    ])->default('pending');

    $form->datetime('payment_date', __('Payment Date'))->default(now());

    // Calculate principal and interest split before saving
    $form->saving(function (Form $form) {
        // Get the input values
        $credit_loan_id = $form->input('credit_loan_id');
        $amount_paid = $form->input('amount_paid');

        // Get the loan
        $loan = CreditLoan::findOrFail($credit_loan_id);

        // Get total amount including interest
        $totalAmountToPay = $loan->loan_amount + $loan->total_interest;

        // Get existing confirmed payments
        $existingPaid = $loan->repayments()
            ->where('status', 'confirmed')
            ->sum('principal_paid');

        // All payment goes to principal first
        $remainingPrincipal = $loan->loan_amount - $existingPaid;

        if ($amount_paid <= $remainingPrincipal) {
            // If payment is less than or equal to remaining principal, all goes to principal
            $form->input('principal_paid', $amount_paid);
            $form->input('interest_paid', 0);
        } else {
            // If payment is more than remaining principal, split it
            $form->input('principal_paid', $remainingPrincipal);
            $form->input('interest_paid', $amount_paid - $remainingPrincipal);
        }

        // Calculate total remaining (principal + interest)
        $totalPaidSoFar = $existingPaid + $amount_paid;
        $remaining_balance = $totalAmountToPay - $totalPaidSoFar;

        // Set the remaining balance
        $form->input('remaining_balance', $remaining_balance);
    });

    return $form;
}
}
