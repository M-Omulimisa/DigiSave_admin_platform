<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class ActivateSacco extends RowAction
{
    public $name = 'Activate';

    public function handle(Model $model)
    {
        // Update the status of the Sacco to 'active'
        $model->status = 'active';
        $model->save();

        return $this->response()->success('Sacco activated successfully')->refresh();
    }

    public function dialog()
    {
        $this->confirm('Are you sure you want to activate this Sacco?');
    }
}
