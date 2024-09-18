<?php

namespace App\Http\Controllers;

use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Assuming User is the model for your users
use Illuminate\Support\Facades\Hash; // To check the password

class NewAuthController extends BaseAuthController
{
    protected $loginView = 'old_login';

    public function getLogin()
    {
        Log::info('Login page accessed.');

        if ($this->guard()->check()) {
            Log::info('User already authenticated, redirecting to dashboard.');
            return redirect($this->redirectPath());
        }

        return view($this->loginView);
    }

    public function postLogin(Request $request)
    {
        // Log the login attempt
        Log::info('Login attempt with username: ' . $request->input('username'));

        // Validation rules
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            Log::warning('Login validation failed for username: ' . $request->input('username'));
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only(['username', 'password']);

        // Check if the username is actually an email
        if (filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $credentials['username'])->first();
        } else {
            $user = User::where('username', $credentials['username'])->first();
        }

        // Check if the user exists
        if (!$user) {
            Log::warning('Login failed: User not found for username: ' . $credentials['username']);
            return back()->withErrors([
                'username' => 'The user does not exist.',
            ])->withInput();
        }

        // Check if the password matches
        if (!Hash::check($credentials['password'], $user->password)) {
            Log::warning('Login failed: Incorrect password for user: ' . $credentials['username']);
            return back()->withErrors([
                'password' => 'The password is incorrect.',
            ])->withInput();
        }

        // Optionally, check if the user is active (if you have a status or active field in your User model)
        if (isset($user->status) && $user->status != 'Active') {
            Log::warning('Login failed: User account inactive for user: ' . $credentials['username']);
            return back()->withErrors([
                'username' => 'The account is inactive. Please contact support.',
            ])->withInput();
        }

        // Attempt to authenticate
        if (Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], $request->get('remember'))) {
            Log::info('Login successful for user: ' . $user->email);
            return $this->sendLoginResponse($request);
        }

        Log::warning('Login failed for user: ' . $credentials['username']);
        return back()->withErrors([
            'auth' => 'Failed to login. Please try again.',
        ])->withInput();
    }
}
