<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Crop;
use App\Models\Garden;
use App\Models\GardenActivity;
use App\Models\Group;
use App\Models\Location;
use App\Models\Organization;
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
        
        // If you also want to filter out users with user_type '4' (agent), you can do the following:
        $filteredUsers = $filteredUsers->reject(function ($user) {
            return $user->user_type === '4';
        });
        
        
        // Calculate various statistics
        $totalSaccos = Sacco::count();
        $totalMembers = $filteredUsers->count();
        $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();

        $villageAgents = User::where('user_type', '4')->count();

        $organisationCount = Organization::count();

        // Calculate percentage of youth members based on date of birth
        $youthMembersPercentage = ($totalMembers > 0)
    ? $filteredUsers->filter(function ($user) {
        return Carbon::parse($user->dob)->age < 35;
    })->count() / $totalMembers * 100
    : 0;
        
        // Female members
        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();
        $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);
        
        // Male members
        $maleUsers = $filteredUsers->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);

        // $maleUserIds = User::where('sex', 'Male')->pluck('id');

        // $maleTotalBalance = Transaction::where('type', 'SHARE')
        //     ->whereIn('user_id', $maleUserIds)
        //     ->sum('balance');
        // $maleTotalBalance = number_format($maleTotalBalance, 2);
        
        // Youth members (assuming under 35 years old)
        $youthUsers = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();
        $youthTotalBalance = number_format($youthUsers->sum('balance'));
        
        // PWD members
        $pwdUsers = User::where('pwd', 'Yes');
        $pwdMembersCount = $pwdUsers->count();
        $pwdUserIds = User::where('pwd', 'Yes')->pluck('id');


        $pwdTotalBalance = Transaction::where('type', 'SHARE')
            ->whereIn('user_id', $pwdUserIds)
            ->sum('balance');
        
        $pwdTotalBalance = number_format($pwdTotalBalance, 2);

        // Loans disbursed to women
        $loansDisbursedToWomen = Transaction::whereIn('user_id', User::where('sex', 'Female')->pluck('id'))
                                            ->where('type', 'LOAN')
                                            ->count();
        
        // Loans disbursed to men
        $loansDisbursedToMen = Transaction::whereIn('user_id',  User::where('sex', 'Male')->pluck('id'))
                                          ->where('type', 'LOAN')
                                          ->count();
        
        // Loans disbursed to youths (assuming youths are under 35 years old)
        $youthIds = User::where(function ($query) {
            return $query->where('dob', '>', now()->subYears(35));
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
            
            $totalSaccosLink = url('saccos');
            $villageAgentsLink = url('agents');
            $organisationCountLink = url('organisation');
            $totalMembersLink = url('members');
            $totalPwdMembersLink = url('members');
            $youthMembersPercentageLink = url('members');
            
            return $content
            ->header('<div style="text-align: center; color: #039103; font-size: 30px; font-weight: bold; padding-top: 20px;">DigiSave VSLA Platform</div>')
            ->body('<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                view('widgets.statistics', [
                    'totalSaccos' => $totalSaccos,
                    'villageAgents' => $villageAgents,
                    'organisationCount' => $organisationCount,
                    'totalMembers' => $totalMembers,
                    'totalPwdMembers' => $pwdMembersCount,
                    'youthMembersPercentage' => number_format($youthMembersPercentage, 2),
                    'totalSaccosLink' => $totalSaccosLink,
                    'villageAgentsLink' => $villageAgentsLink,
                    'organisationCountLink' => $organisationCountLink,
                    'totalMembersLink' => $totalMembersLink,
                    'totalPwdMembersLink' => $totalPwdMembersLink,
                    'youthMembersPercentageLink' => $youthMembersPercentageLink,
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
  
