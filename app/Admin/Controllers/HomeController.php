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
use App\Models\OrganizationAssignment;
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
        $filteredUsers = $filteredUsers->reject(function ($user) {
            return $user->user_type === '5';
        });

        if ($admin->isRole('org')) {
            $orgIds = Organization::where('agent_id', $admin->id)->pluck('id')->toArray();
            
            // Retrieve the sacco IDs associated with the organization IDs
            $saccoIds = OrganizationAssignment::whereIn('organization_id', $orgIds)->pluck('sacco_id')->toArray();
            
            // Calculate various statistics based on organization-specific data
            $totalSaccos = Sacco::whereIn('id', $saccoIds)->count();
            $organisationCount = Organization::whereIn('id', $orgIds)->count();
            $totalMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->count();
            $totalPwdMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->where('pwd', 'yes')->count();
            $villageAgents = User::whereIn('sacco_id', $saccoIds)->where('user_type', '4')->count();
            $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->whereIn('sacco_id', $saccoIds)->filter(function ($user) {
                return Carbon::parse($user->dob)->age < 35;
            })->count() / $totalMembers * 100 : 0;
        
            // Filter users for calculating balances
            $filteredUsersForBalances = $filteredUsers->whereIn('sacco_id', $saccoIds);

                    // Calculate statistics for PWD members
        $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
        $pwdMembersCount = $pwdUsers->count();
        $pwdUserIds = $pwdUsers->pluck('id');
        
        $pwdTotalBalance = Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'SHARE')
            ->whereIn('user_id', $pwdUserIds)
            ->sum('balance');
        $pwdTotalBalance = number_format($pwdTotalBalance, 2);

        // Loans disbursed to women
        $loansDisbursedToWomen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', User::whereIn('sacco_id', $saccoIds)->where('sex', 'Female')->pluck('id'))
                                            ->where('type', 'LOAN')
                                            ->count();
        
        // Loans disbursed to men
        $loansDisbursedToMen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id',  User::whereIn('sacco_id', $saccoIds)->where('sex', 'Male')->pluck('id'))
                                          ->where('type', 'LOAN')
                                          ->count();
                // Loans disbursed to youths (assuming youths are under 35 years old)
                $youthIds = User::whereIn('sacco_id', $saccoIds)->where(function ($query) {
                    return $query->where('dob', '>', now()->subYears(35));
                })->pluck('id');
                
                
        $loansDisbursedToYouths = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $youthIds)
                                                     ->where('type', 'LOAN')
                                                     ->count();
        
        
        $loanSumForWomen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->whereIn('sacco_id', $saccoIds)->where('sex', 'Female')->pluck('id'))
                                        ->where('type', 'LOAN')
                                        ->sum('amount');
        
        
        $loanSumForMen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->whereIn('sacco_id', $saccoIds)->where('sex', 'Male')->pluck('id'))
                                      ->where('type', 'LOAN')
                                      ->sum('amount');
                                
        $youthIds = $filteredUsers->whereIn('sacco_id', $saccoIds)->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        })->pluck('id');
        
        $loanSumForYouths = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $youthIds)
                                            ->where('type', 'LOAN')
                                            ->sum('amount');
                                                                     
                                
        // Calculate total loan amount disbursed
        $totalLoanAmount = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->pluck('id'))
                                    ->where('type', 'LOAN')
                                    ->sum('amount');
                                
                                
        // Calculate total loan amount disbursed
        $totalLoanBalance = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->pluck('id'))
                                    ->where('type', 'LOAN')
                                    ->sum('balance');

        $transactions = Transaction::whereIn('sacco_id', $saccoIds)->get();

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
                                    
                                                                        
        } else {
            // Calculate various statistics without organization-specific data
            $totalSaccos = Sacco::count();
            $organisationCount = Organization::count();
            $totalMembers = $filteredUsers->count();
            $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();
            $villageAgents = User::where('user_type', '4')->count();
            $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->filter(function ($user) {
                return Carbon::parse($user->dob)->age < 35;
            })->count() / $totalMembers * 100 : 0;
        
            // All users for calculating balances
            $filteredUsersForBalances = $filteredUsers;
                    // Calculate statistics for PWD members
        $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
        $pwdMembersCount = $pwdUsers->count();
        $pwdUserIds = $pwdUsers->pluck('id');
        
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



        }
        
        // Calculate statistics for female members
        $femaleUsers = $filteredUsersForBalances->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();
        $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);
        
        // Calculate statistics for male members
        $maleUsers = $filteredUsersForBalances->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);
        
        // Calculate statistics for youth members
        $youthUsers = $filteredUsersForBalances->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();
        $youthTotalBalance = number_format($youthUsers->sum('balance'), 2);

//----------------------------------------------------------------------------------------------------//
        






            
            // $totalSaccosLink = url('saccos');
            // $villageAgentsLink = url('agents');
            // $organisationCountLink = url('organisation');
            // $totalMembersLink = url('members');
            // $totalPwdMembersLink = url('members');
            // $youthMembersPercentageLink = url('members');
            
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
                    // 'totalSaccosLink' => $totalSaccosLink,
                    // 'villageAgentsLink' => $villageAgentsLink,
                    // 'organisationCountLink' => $organisationCountLink,
                    // 'totalMembersLink' => $totalMembersLink,
                    // 'totalPwdMembersLink' => $totalPwdMembersLink,
                    // 'youthMembersPercentageLink' => $youthMembersPercentageLink,
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
  
