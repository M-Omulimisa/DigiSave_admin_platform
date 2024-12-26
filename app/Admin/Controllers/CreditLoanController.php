<?php

namespace App\Admin\Controllers;

use App\Models\CreditLoan;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Actions\RowAction;

class CreditLoanController extends AdminController
{
    protected $title = 'Credit Loans';

    /**
     * Create the grid view for CreditLoan.
     *
     * @return Grid
     */
    protected function grid()
{
    $grid = new Grid(new CreditLoan());

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

    // Add approve, reject, and other actions in each row
    $grid->actions(function ($actions) {
        // Add Approve and Reject buttons
        $actions->add(new ApproveLoan);
        $actions->add(new RejectLoan);
    });

    // Format the date to show clear date
    $grid->column('created_at', __('Created At'))->display(function ($value) {
        return date('F d, Y h:i A', strtotime($value));
    })->sortable();

    return $grid;
}

    /**
     * Create the detail view for CreditLoan.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(CreditLoan::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('sacco.name', __('Sacco'));
        $show->field('loan_amount', __('Loan Amount'));
        $show->field('loan_term', __('Loan Term (months)'));
        $show->field('total_interest', __('Total Interest'));
        $show->field('monthly_payment', __('Monthly Payment'));
        $show->field('loan_purpose', __('Loan Purpose'));
        $show->field('billing_address', __('Billing Address'));
        $show->field('selected_method', __('Payment Method'));
        $show->field('loan_status', __('Loan Status'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Create the form for creating/editing CreditLoan.
     *
     * @return Form
     */
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

        // Whether terms were accepted
        $form->switch('terms_accepted', __('Terms Accepted'))->default(0);

        // Use current address switch
        $form->switch('use_current_address', __('Use Current Address'))->default(0);

        return $form;
    }
}

/**
 * Action class for approving a loan.
 */
class ApproveLoan extends RowAction
{
    public $name = 'Approve';

    public function handle(CreditLoan $loan)
    {
        // Call the approveLoan method from the model
        $result = $loan->approveLoan();

        if ($result['status'] === 'success') {
            return $this->response()->success($result['message'])->refresh();
        }

        return $this->response()->error($result['message'])->refresh();
    }
}

/**
 * Action class for rejecting a loan.
 */
class RejectLoan extends RowAction
{
    public $name = 'Reject';

    public function handle(CreditLoan $loan)
    {
        // Call the rejectLoan method from the model
        $result = $loan->rejectLoan();

        if ($result['status'] === 'success') {
            return $this->response()->success($result['message'])->refresh();
        }

        return $this->response()->error($result['message'])->refresh();
    }
}
