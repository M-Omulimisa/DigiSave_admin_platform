<?php

namespace App\Admin\Actions;

use App\Models\CreditLoan;
use Encore\Admin\Actions\RowAction;

class ApproveLoan extends RowAction
{
    public $name = 'Approve';

    public function handle(CreditLoan $loan)
    {
        $result = $loan->approveLoan();

        if ($result['status'] === 'success') {
            return $this->response()->success($result['message'])->refresh();
        }

        return $this->response()->error($result['message'])->refresh();
    }
}
