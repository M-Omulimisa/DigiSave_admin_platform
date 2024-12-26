<?php

namespace App\Admin\Controllers;

use App\Models\CreditLoan;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Actions\ApproveLoan;
use App\Admin\Actions\RejectLoan;
use App\Admin\Actions\DisburseLoan;

class CreditLoanController extends AdminController
{
    protected $title = 'Credit Loans';

    protected function grid()
    {
        $grid = new Grid(new CreditLoan());

        // Add filter buttons at the top
        $grid->filter(function($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();

            // Add a filter for loan status
            $filter->equal('loan_status', 'Loan Status')->select([
                'approved' => 'Approved',
                'pending' => 'Pending',
                'rejected' => 'Rejected'
            ]);

            // Add a filter for disbursement status
            $filter->equal('disbursement_status', 'Disbursement Status')->select([
                'pending' => 'Pending',
                'disbursed' => 'Disbursed'
            ]);

            // Add a filter for sacco
            $filter->equal('sacco_id', 'Sacco')->select(Sacco::all()->pluck('name', 'id'));
        });

        // Add quick filter buttons
        $grid->header(function ($query) {
            return "<div style='margin-bottom: 10px;'>
                <a href='?' class='btn btn-sm btn-default' style='margin-right: 10px;'>All</a>
                <a href='?loan_status=approved' class='btn btn-sm btn-success' style='margin-right: 10px;'>Approved Loans</a>
                <a href='?disbursement_status=disbursed' class='btn btn-sm btn-info'>Disbursed Loans</a>
            </div>";
        });

        // Display basic fields in the grid
        $grid->column('id', __('ID'))->sortable();
        $grid->column('sacco.name', __('Sacco'))->sortable();

        // Format loan amount in UGX
        $grid->column('loan_amount', __('Loan Amount'))->display(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        })->sortable();

        $grid->column('loan_term', __('Loan Term (months)'))->sortable();

        // Format total interest in UGX
        $grid->column('total_interest', __('Total Interest'))->display(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        })->sortable();

        // Format monthly payment in UGX
        $grid->column('monthly_payment', __('Monthly Payment'))->display(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        })->sortable();

        $grid->column('loan_purpose', __('Loan Purpose'))->sortable();
        $grid->column('billing_address', __('Billing Address'))->sortable();
        $grid->column('selected_method', __('Payment Method'))->sortable();

        // Display loan status with button-style background color
        $grid->column('loan_status', __('Loan Status'))->display(function ($status) {
            $color = $status === 'approved' ? 'green' : ($status === 'rejected' ? 'red' : 'orange');
            return "<button style='background-color: {$color}; color: white; padding: 5px 10px; border: none; border-radius: 5px;'>{$status}</button>";
        })->sortable();

        // Add disbursement status column
        $grid->column('disbursement_status', __('Disbursement'))->display(function ($status) {
            $color = $status === 'disbursed' ? 'green' : 'blue';
            return "<button style='background-color: {$color}; color: white; padding: 5px 10px; border: none; border-radius: 5px;'>{$status}</button>";
        })->sortable();

        // Add payment details column for disbursed loans
        $grid->column('payment_details', __('Payment Details'))->display(function () {
            if ($this->disbursement_status === 'disbursed') {
                return nl2br(e($this->payment_details));
            }
            return '-';
        });

        $grid->column('disbursed_at', __('Disbursed At'))->display(function ($value) {
            return $value ? date('F d, Y h:i A', strtotime($value)) : '-';
        })->sortable();

        // Add actions
        $grid->actions(function ($actions) {
            $actions->add(new ApproveLoan);
            $actions->add(new RejectLoan);
            $actions->add(new DisburseLoan);
        });

        // Format the created_at date
        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        })->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(CreditLoan::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('sacco.name', __('Sacco'));
        $show->field('loan_amount', __('Loan Amount'))->as(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        });
        $show->field('loan_term', __('Loan Term (months)'));
        $show->field('total_interest', __('Total Interest'))->as(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        });
        $show->field('monthly_payment', __('Monthly Payment'))->as(function ($value) {
            return 'UGX ' . number_format(round($value), 0, '.', ',');
        });
        $show->field('loan_purpose', __('Loan Purpose'));
        $show->field('billing_address', __('Billing Address'));
        $show->field('selected_method', __('Payment Method'));
        $show->field('loan_status', __('Loan Status'));
        $show->field('disbursement_status', __('Disbursement Status'));
        $show->field('disbursed_at', __('Disbursed At'))->as(function ($value) {
            return $value ? date('F d, Y h:i A', strtotime($value)) : '-';
        });
        $show->field('disbursement_reference', __('Disbursement Reference'));
        $show->field('payment_details', __('Payment Details'))->as(function () {
            return nl2br(e($this->payment_details));
        });
        $show->field('created_at', __('Created At'))->as(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        });
        $show->field('updated_at', __('Updated At'))->as(function ($value) {
            return date('F d, Y h:i A', strtotime($value));
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new CreditLoan());

        // Dropdown to select Sacco
        $form->select('sacco_id', __('Sacco'))->options(Sacco::all()->pluck('name', 'id'))->rules('required');

        // Fields for loan details
        $form->decimal('loan_amount', __('Loan Amount'))->rules('required|numeric|min:0');
        $form->number('loan_term', __('Loan Term (months)'))->rules('required|integer|min:1');
        $form->decimal('total_interest', __('Total Interest'))->rules('required|numeric|min:0');
        $form->decimal('monthly_payment', __('Monthly Payment'))->rules('required|numeric|min:0');
        $form->text('loan_purpose', __('Loan Purpose'))->rules('required');
        $form->text('billing_address', __('Billing Address'))->rules('required');

        // Payment method selection and conditional fields
        $form->select('selected_method', __('Payment Method'))->options([
            'bank' => 'Bank Transfer',
            'airtel' => 'Airtel Money',
            'mtn' => 'MTN Mobile Money'
        ])->rules('required')->when('bank', function (Form $form) {
            $form->text('selected_bank', __('Selected Bank'))->rules('required');
            $form->text('account_number', __('Account Number'))->rules('required');
            $form->text('account_name', __('Account Name'))->rules('required');
        })->when('airtel', function (Form $form) {
            $form->text('phone_number', __('Phone Number'))->rules('required');
            $form->text('account_name', __('Account Name'))->rules('required');
        })->when('mtn', function (Form $form) {
            $form->text('phone_number', __('Phone Number'))->rules('required');
            $form->text('account_name', __('Account Name'))->rules('required');
        });

        // Loan status field with default value "pending"
        $form->select('loan_status', __('Loan Status'))->options([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ])->default('pending');

        // Disbursement status field (readonly)
        $form->select('disbursement_status', __('Disbursement Status'))->options([
            'pending' => 'Pending',
            'disbursed' => 'Disbursed'
        ])->default('pending')->readonly();

        // Whether terms were accepted
        $form->switch('terms_accepted', __('Terms Accepted'))->default(0);

        // Use current address switch
        $form->switch('use_current_address', __('Use Current Address'))->default(0);

        return $form;
    }
}
