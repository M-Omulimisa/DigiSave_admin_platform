<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DeleteConfirmation extends RowAction
{
    public $name = 'Delete';

    /**
     * Don't display confirmation dialog - we'll use our custom page
     */
    public function getConfirmation()
    {
        return null;
    }

    /**
     * Set the URL for the action
     */
    public function href()
    {
        // Use the regular admin URL format
        return admin_url('saccos/'.$this->getKey().'/delete-confirmation');
    }

    /**
     * This method will be called when the user click this action.
     */
    public function handle(Model $model, Request $request)
    {
        // This is a no-op as we're using href() to redirect
        return $this->response()->success('Redirecting...')->redirect(
            admin_url('saccos/'.$model->id.'/delete-confirmation')
        );
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-trash';
    }

    /**
     * @return array
     */
    public function getClasses()
    {
        return ['text-danger'];
    }
}
