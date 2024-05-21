<?php

namespace App\Http\Controllers;

use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;

class NewAuthController extends BaseAuthController
{
    protected $loginView = 'old_login';

    public function getLogin()
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }

        return view($this->loginView);
    }
}
