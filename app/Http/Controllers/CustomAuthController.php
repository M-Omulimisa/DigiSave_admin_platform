<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class CustomAuthController extends Controller
{

    use AuthenticatesUsers;


    protected $redirectTo;


    public function __construct()
    {
        $this->redirectTo = route('client.login');
        $this->middleware('guest')->except('logout');
    }


    public function showLoginForm()
    {
        return view('login');
    }


    public function username()
    {
        return 'email';
    }


    protected function validateLogin(Request $request)
    {
        $this->validate(
            $request,
            [
                'email' => 'required|email|string',
                'password' => 'required|string',
            ],
            [
                'email.required' => 'Email is required',
                'password.required' => 'Password is required',
            ]
        );
    }




    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }



    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect('/');
    }



    protected function attemptLogin( Request $request )
    {
        $credentials = array_merge( $this->credentials($request), ['status'=>1] );


        if( $this->guard()->attempt( $credentials, $request->filled('remember') ) ) {
            return true;
        }


        return false;
    }



    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password', 'status');
    }
}
