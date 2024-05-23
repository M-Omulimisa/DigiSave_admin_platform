<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Crop;
use App\Models\Garden;
use App\Models\GardenActivity;
use App\Models\Group;
use App\Models\Location;
use App\Models\OrgAllocation;
use App\Models\Organization;
use App\Models\OrganizationAssignment;
use App\Models\Person;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use App\Models\VslaOrganisation;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use SplFileObject;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        $users = User::all();
        $admin = Admin::user();
        $adminId = $admin->id;

        $totalAccounts = User::where('user_type', 'Admin')->count();
        $totalOrgAdmins = User::where('user_type', '5')->count();

        $filteredUsers = $users->reject(function ($user) use ($adminId) {
            return $user->id === $adminId && $user->user_type === 'Admin';
        });

        $filteredUsers = $filteredUsers->reject(function ($user) {
            return $user->user_type === '4';
        });

        $filteredUsers = $filteredUsers->reject(function ($user) {
            return $user->user_type === '5';
        });

        $filteredUsers = $filteredUsers->filter(function ($user) {
            return $user->user_type === null || !in_array($user->user_type, ['Admin', '5']);
        });

        // Aggregate users by registration date
        $userRegistrations = $users->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y-m-d'); // Group by day
        });

        $registrationDates = $userRegistrations->keys()->toArray();
        $registrationCounts = $userRegistrations->map(function ($item) {
            return count($item);
        })->values()->toArray();

        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if (!$orgAllocation) {
                Auth::logout();
                $message = "You are not allocated to any organization. Please contact M-Omulimisa Service Help for assistance.";
                Session::flash('warning', $message);
                admin_error($message);
                return redirect('auth/logout');
            }

            $organization = VslaOrganisation::find($orgAllocation->vsla_organisation_id);
            $orgIds = $orgAllocation->vsla_organisation_id;
            $orgName = $organization->name;
            // $logoUrl = env('APP_URL') . 'storage/' . $organization->logo;
            $logoUrl = '';
            if ($organization->name === 'International Institute of Rural Reconstruction (IIRR)') {
                $logoUrl = 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png';
            } elseif ($organization->name === 'Ripple Effect Uganda') {
                $logoUrl = 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png';
            }
            $organizationContainer = '<div style="text-align: center; padding-bottom: 25px;"><img src="' . $logoUrl . '" alt="' . $organization->name . '" class="img-fluid rounded-circle" style="max-width: 200px;"></div>';

            if ($organization->name === 'International Institute of Rural Reconstruction (IIRR)' || $organization->name === 'Ripple Effect Uganda' || $organization->name === 'Test Org') {
                // Use the default admin statistics
                $totalSaccos = Sacco::count();
                $organisationCount = VslaOrganisation::count();
                $totalMembers = $filteredUsers->count();
                $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();
                $villageAgents = User::where('user_type', '4')->count();
                $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->filter(function ($user) {
                    return Carbon::parse($user->dob)->age < 35;
                })->count() / $totalMembers * 100 : 0;

                $filteredUsersForBalances = $filteredUsers;
                $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
                $pwdMembersCount = $pwdUsers->count();
                $pwdUserIds = $pwdUsers->pluck('id');

                $pwdTotalBalance = Transaction::where('type', 'SHARE')
                    ->whereIn('user_id', $pwdUserIds)
                    ->sum('balance');
                $pwdTotalBalance = number_format($pwdTotalBalance, 2);

                $loansDisbursedToWomen = Transaction::whereIn('user_id', User::where('sex', 'Female')->pluck('id'))
                    ->where('type', 'LOAN')
                    ->count();

                $loansDisbursedToMen = Transaction::whereIn('user_id', User::where('sex', 'Male')->pluck('id'))
                    ->where('type', 'LOAN')
                    ->count();

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

                $totalLoanAmount = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                    ->where('type', 'LOAN')
                    ->sum('amount');

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
            } else {
                $saccoIds = OrganizationAssignment::where('organization_id', $orgIds)->pluck('sacco_id')->toArray();
                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgIds)->pluck('vsla_organisation_id')->toArray();
                $totalOrgAdmins = count($OrgAdmins);

                $totalSaccos = Sacco::whereIn('id', $saccoIds)->count();
                $organisationCount = VslaOrganisation::where('id', $orgIds)->count();
                $totalMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->count();
                $totalAccounts = $filteredUsers->where('user_type', 'Admin')->whereIn('sacco_id', $saccoIds)->count();
                $totalPwdMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->where('pwd', 'yes')->count();
                $villageAgents = User::whereIn('sacco_id', $saccoIds)->where('user_type', '4')->count();
                $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->whereIn('sacco_id', $saccoIds)->filter(function ($user) {
                    return Carbon::parse($user->dob)->age < 35;
                })->count() / $totalMembers * 100 : 0;

                $filteredUsersForBalances = $filteredUsers->whereIn('sacco_id', $saccoIds);
                $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
                $pwdMembersCount = $pwdUsers->count();
                $pwdUserIds = $pwdUsers->pluck('id');

                $pwdTotalBalance = Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'SHARE')
                    ->whereIn('user_id', $pwdUserIds)
                    ->sum('balance');
                $pwdTotalBalance = number_format($pwdTotalBalance, 2);

                $loansDisbursedToWomen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', User::whereIn('sacco_id', $saccoIds)->where('sex', 'Female')->pluck('id'))
                    ->where('type', 'LOAN')
                    ->count();

                $loansDisbursedToMen = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', User::whereIn('sacco_id', $saccoIds)->where('sex', 'Male')->pluck('id'))
                    ->where('type', 'LOAN')
                    ->count();

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

                $loanSumForYouths = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $youthIds)
                    ->where('type', 'LOAN')
                    ->sum('amount');

                $totalLoanAmount = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->pluck('id'))
                    ->where('type', 'LOAN')
                    ->sum('amount');

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
            }
        } else {
            $organizationContainer = '';
            $orgName = 'DigiSave VSLA Platform';
            $totalSaccos = Sacco::count();
            $organisationCount = VslaOrganisation::count();
            $totalMembers = $filteredUsers->count();
            $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();
            $villageAgents = User::where('user_type', '4')->count();
            $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->filter(function ($user) {
                return Carbon::parse($user->dob)->age < 35;
            })->count() / $totalMembers * 100 : 0;

            $filteredUsersForBalances = $filteredUsers;
            $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
            $pwdMembersCount = $pwdUsers->count();
            $pwdUserIds = $pwdUsers->pluck('id');

            $pwdTotalBalance = Transaction::where('type', 'SHARE')
                ->whereIn('user_id', $pwdUserIds)
                ->sum('balance');
            $pwdTotalBalance = number_format($pwdTotalBalance, 2);

            $loansDisbursedToWomen = Transaction::whereIn('user_id', User::where('sex', 'Female')->pluck('id'))
                ->where('type', 'LOAN')
                ->count();

            $loansDisbursedToMen = Transaction::whereIn('user_id', User::where('sex', 'Male')->pluck('id'))
                ->where('type', 'LOAN')
                ->count();

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

            $loanSumForYouths = Transaction::whereIn('user_id', $youthIds)
                ->where('type', 'LOAN')
                ->sum('amount');

            $totalLoanAmount = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                ->where('type', 'LOAN')
                ->sum('amount');

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

        $femaleUsers = $filteredUsersForBalances->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();
        $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);

        $maleUsers = $filteredUsersForBalances->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);

        $youthUsers = $filteredUsersForBalances->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();
        $youthTotalBalance = number_format($youthUsers->sum('balance'), 2);

        // Collect user locations
        $userLocations = $users->map(function ($user) {
            return [
                'name' => $user->name,
                'lat' => $user->location_lat,
                'lon' => $user->location_long,
            ];
        })->filter(function ($location) {
            return !is_null($location['lat']) && !is_null($location['lon']);
        });

        // Gender distribution
        $genderDistribution = User::select('sex', DB::raw('count(*) as total'))
            ->groupBy('sex')
            ->pluck('total', 'sex');

        // Age distribution
        $ageDistribution = User::select(DB::raw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age'), DB::raw('count(*) as total'))
            ->groupBy('age')
            ->pluck('total', 'age');

        // Get users with their balances
        $usersWithBalances = $users->map(function ($user) {
            return [
                'name' => $user->name,
                'balance' => $user->balance,
            ];
        })->sortByDesc('balance');

        // Collect user balances and user_type
        $usersWithBalances = $users->map(function ($user) {
            return [
                'name' => $user->name,
                'balance' => $user->balance,
                'user_type' => $user->user_type, // Include user_type in the mapped data
            ];
        })->sortByDesc('balance');

        // Filter users with user_type 'Admin' and get top 10 by balance
        $adminUsersWithBalances = $usersWithBalances->filter(function ($user) {
            return $user['user_type'] === 'Admin'; // Access user_type as an array element
        })->take(10); // No need to sort again as it's already sorted

        // Prepare data for the chart
        $userBalances = $adminUsersWithBalances->map(function ($user) {
            return ['name' => $user['name'], 'balance' => $user['balance']];
        });

        return $content
            ->header('<div style="text-align: center; color: #039103; font-size: 30px; font-weight: bold; padding-top: 20px;">' . $orgName . '</div>')
            ->body($organizationContainer . '<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                view('widgets.statistics', [
                    'totalSaccos' => $totalSaccos,
                    'villageAgents' => $villageAgents,
                    'organisationCount' => $organisationCount,
                    'totalMembers' => $totalMembers,
                    'totalAccounts' => $totalAccounts,
                    'totalOrgAdmins' => $totalOrgAdmins,
                    'totalPwdMembers' => $totalPwdMembers,
                    'youthMembersPercentage' => number_format($youthMembersPercentage, 2),
                ]) .
                view('widgets.user_registrations', [
                    'registrationDates' => $registrationDates,
                    'registrationCounts' => $registrationCounts,
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
                // view('widgets.total_loan_amount', [
                //     'totalLoanAmount' => $totalLoanAmount,
                //     'totalLoanBalance' => $totalLoanBalance,
                // ]) .
                // view('widgets.gender_and_age_distribution', [
                //     'genderDistribution' => $genderDistribution,
                //     'ageDistribution' => $ageDistribution,
                // ]) .
                view('widgets.users_with_balances', [
                    'userBalances' => $userBalances,
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
