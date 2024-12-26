<?php

namespace App\Admin\Actions;

use App\Models\CreditLoan;
use Encore\Admin\Actions\RowAction;

class RejectLoan extends RowAction
{
    public $name = 'Reject';

    public function handle(CreditLoan $loan)
    {
        $result = $loan->rejectLoan();

        if ($result['status'] === 'success') {
            return $this->response()->success($result['message'])->refresh();
        }

        return $this->response()->error($result['message'])->refresh();
    }
}
