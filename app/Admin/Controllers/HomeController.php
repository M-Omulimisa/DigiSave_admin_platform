<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Crop;
use App\Models\Garden;
use App\Models\GardenActivity;
use App\Models\Group;
use App\Models\Location;
use App\Models\Person;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use SplFileObject;

class HomeController extends Controller
{
    // Existing methods...

    public function index(Content $content)
    {
        
        $users = User::all(); 
        
        $admin = Admin::user();
        $adminId = $admin->id;
        
        // Filter out the logged-in admin from the users
        $filteredUsers = $users->reject(function ($user) use ($adminId) {
            return $user->id === $adminId && $user->user_type === 'Admin';
        });
        
        // Calculate various statistics
        $totalSaccos = Sacco::count();
        $totalMembers = $filteredUsers->count();
        $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();

        // Calculate percentage of youth members based on date of birth
        $youthMembersPercentage = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35; 
        })->count() / $totalMembers * 100;
        
        // Female members
        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();
        $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);
        
        // Male members
        $maleUsers = $filteredUsers->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);
        
        // Youth members (assuming under 35 years old)
        $youthUsers = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();
        $youthTotalBalance = number_format($youthUsers->sum('balance'));
        
        // PWD members
        $pwdUsers = $filteredUsers->where('pwd', 'yes');
        $pwdMembersCount = $pwdUsers->count();
        $pwdTotalBalance = number_format($pwdUsers->sum('balance'), 2);

        // Loans disbursed to women
        $loansDisbursedToWomen = Transaction::whereIn('user_id', $filteredUsers->where('sex', 'Female')->pluck('id'))
                                            ->where('type', 'LOAN')
                                            ->count();
        
        // Loans disbursed to men
        $loansDisbursedToMen = Transaction::whereIn('user_id', $filteredUsers->where('sex', 'Male')->pluck('id'))
                                          ->where('type', 'LOAN')
                                          ->count();
        
        // Loans disbursed to youths (assuming youths are under 35 years old)
        $youthIds = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        })->pluck('id');
        
        $loansDisbursedToYouths = Transaction::whereIn('user_id', $youthIds)
                                             ->where('type', 'LOAN')
                                             ->count();


$loanSumForWomen = Transaction::whereIn('user_id', $filteredUsers->where('sex', 'Female')->pluck('id'))
                                ->where('type', 'LOAN')
                                ->sum('amount');


$loanSumForMen = Transaction::whereIn('user_id', $filteredUsers->where('sex', 'Male')->pluck('id'))
                              ->where('type', 'LOAN')
                              ->sum('amount');


$youthIds = $filteredUsers->filter(function ($user) {
    return Carbon::parse($user->dob)->age < 35;
})->pluck('id');

$loanSumForYouths = Transaction::whereIn('user_id', $youthIds)
                                 ->where('type', 'LOAN')
                                 ->sum('amount');
                                 
                                 
            // Calculate total loan amount disbursed
            $totalLoanAmount = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                                        ->where('type', 'LOAN')
                                        ->sum('amount');
                                 
                                 
            // Calculate total loan amount disbursed
            $totalLoanBalance = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                                        ->where('type', 'LOAN')
                                        ->sum('balance');

            $transactions = Transaction::all();

            
            $monthYearList = [];
            $totalSavingsList = [];
            
            foreach ($transactions as $transaction) {
                $monthYear = Carbon::parse($transaction->created_at)->format('F Y');
            
                if (!in_array($monthYear, $monthYearList)) {
                    $monthYearList[] = $monthYear;
                }
            
                if (array_key_exists($monthYear, $totalSavingsList)) {
                    $totalSavingsList[$monthYear] += $transaction->amount;
                } else {
                    $totalSavingsList[$monthYear] = $transaction->amount;
                }
            }
            
            return $content
                ->header('<span style="color: #3B88D4; font-size: 30px; font-weight: bold;">Welcome to DigiSave Village Savings and Loans Associations (VSLAs) Admin platform</span>')
                ->body('<div style="background-color: #E9F9E9; padding: 10px; border-radius: 5px;">' .
                    view('widgets.statistics', [
                        'totalSaccos' => $totalSaccos,
                        'totalMembers' => $totalMembers,
                        'totalPwdMembers' => $totalPwdMembers,
                        'youthMembersPercentage' => number_format($youthMembersPercentage, 2),
                    ]) .
                    view('widgets.card_set', [
                        'femaleMembersCount' => $femaleMembersCount,
                        'femaleTotalBalance' => $femaleTotalBalance,
                        'maleMembersCount' => $maleMembersCount,
                        'maleTotalBalance' => $maleTotalBalance,
                        'youthMembersCount' => $youthMembersCount,
                        'youthTotalBalance' => $youthTotalBalance,
                        'pwdMembersCount' => $pwdMembersCount,
                        'pwdTotalBalance' => $pwdTotalBalance,
                    ]) .
                    view('widgets.loan_count', [
                        'loansDisbursedToWomen' => $loansDisbursedToWomen,
                        'loansDisbursedToMen' => $loansDisbursedToMen,
                        'loansDisbursedToYouths' => $loansDisbursedToYouths,
                    ]) .
                    view('widgets.loan_amount', [
                        'loanSumForWomen' => $loanSumForWomen,
                        'loanSumForMen' => $loanSumForMen,
                        'loanSumForYouths' => $loanSumForYouths,
                    ]) .
                    view('widgets.total_loan_amount', [
                        'totalLoanAmount' => $totalLoanAmount,
                        'totalLoanBalance' => $totalLoanBalance
                    ]) .
                    view('widgets.chart_container', [
                        'Female' => $femaleTotalBalance,
                        'Male' => $maleTotalBalance,
                        'monthYearList' => $monthYearList,
                        'totalSavingsList' => $totalSavingsList,
                    ]) .
                '</div>');
                                        
                                    
    }
}
  
