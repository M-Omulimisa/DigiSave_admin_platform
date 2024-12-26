<?php

namespace App\Admin\Actions;

use App\Models\CreditLoan;
use Encore\Admin\Actions\RowAction;

class DisburseLoan extends RowAction
{
    public $name = 'Disburse';

    public function handle(CreditLoan $loan)
    {
        if ($loan->loan_status !== 'approved') {
            return $this->response()->error('Only approved loans can be disbursed.');
        }

        if ($loan->disbursement_status === 'disbursed') {
            return $this->response()->error('Loan has already been disbursed.');
        }

        $loan->disbursement_status = 'disbursed';
        $loan->disbursed_at = now();
        $loan->disbursement_reference = 'DISB-' . uniqid();
        $loan->save();

        return $this->response()->success('Loan has been disbursed successfully.')->refresh();
    }

    public function display($loan)
    {
        return $loan->loan_status === 'approved' && $loan->disbursement_status === 'pending';
    }
}
