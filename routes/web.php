<?php

use App\Admin\Controllers\DistrictsController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\MainController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\Gen;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('policy', function () {
    return view('policy');
});
Route::get('process-users', function () {
    $users = User::where(['processed' => 'No'])->get();
    echo "Total users: " . count($users) . "<br>";
    foreach ($users as $user) {
        $phone_number = Utils::prepare_phone_number($user->phone_number);
        if (!Utils::phone_number_is_valid($phone_number)) {
            $user->process_status = 'Failed';
            $user->processed = 'Yes';
            $user->process_message = 'Invalid phone number: ' . $user->phone_number;
            $user->save();
            echo $user->process_message . "<br>";
            continue;
        }
        $user->processed = 'Yes';
        $user->process_status = 'Valid';
        $user->process_message = 'Phone number is valid.';
        try {
            echo "Processing " . $user->phone_number . "<br>";
            $user->save();
        } catch (\Throwable $th) {
            $user->process_status = 'Failed';
            $user->process_message = $th->getMessage();
            $user->save();
            echo $user->process_message . "<br>";
        }
    }
});

Route::get('/gen-form', function () {
    die(Gen::find($_GET['id'])->make_forms());
})->name("gen-form");


Route::get('generate-class', [MainController::class, 'generate_class']);
Route::get('/gen', function () {
    die(Gen::find($_GET['id'])->do_get());
})->name("register");


/*

Route::get('generate-variables', [MainController::class, 'generate_variables']);
Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/about-us', [MainController::class, 'about_us']);
Route::get('/our-team', [MainController::class, 'our_team']);
Route::get('/news-category/{id}', [MainController::class, 'news_category']);
Route::get('/news-category', [MainController::class, 'news_category']);
Route::get('/news', [MainController::class, 'news_category']);
Route::get('/news/{id}', [MainController::class, 'news']);
Route::get('/members', [MainController::class, 'members']);
Route::get('/dinner', [MainController::class, 'dinner']);
Route::get('/ucc', function(){ return view('chair-person-message'); });
Route::get('/vision-mission', function(){ return view('vision-mission'); });
Route::get('/constitution', function(){ return view('constitution'); });
Route::get('/register', [AccountController::class, 'register'])->name('register');

Route::get('/login', [AccountController::class, 'login'])->name('login')
    ->middleware(RedirectIfAuthenticated::class);

Route::post('/register', [AccountController::class, 'register_post'])
    ->middleware(RedirectIfAuthenticated::class);

Route::post('/login', [AccountController::class, 'login_post'])
    ->middleware(RedirectIfAuthenticated::class);


Route::get('/dashboard', [AccountController::class, 'dashboard'])
    ->middleware(Authenticate::class);


Route::get('/account-details', [AccountController::class, 'account_details'])
    ->middleware(Authenticate::class);

Route::post('/account-details', [AccountController::class, 'account_details_post'])
    ->middleware(Authenticate::class);

Route::get('/logout', [AccountController::class, 'logout']);
 */
