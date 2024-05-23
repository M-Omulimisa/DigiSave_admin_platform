<?php

namespace App\Http\Controllers;

use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function postLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only(['username', 'password']);

        // Check if the username is actually an email
        if (filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
            // Attempt to log in using email
            $credentials['email'] = $credentials['username'];
            unset($credentials['username']);
        }

        if (Auth::attempt($credentials, $request->get('remember'))) {
            return $this->sendLoginResponse($request);
        }

        return back()->withErrors([
            'username' => [trans('auth.failed')],
        ]);
    }
}
