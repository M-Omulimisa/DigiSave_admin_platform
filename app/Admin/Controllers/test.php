<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VslaOrganisation;
use App\Models\VslaOrganisationSacco;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        // Update the status of saccos based on chairperson availability
        foreach (Sacco::where(["processed" => "no"])->get() as $sacco) {
            $chairperson = User::where('sacco_id', $sacco->id)
                ->whereHas('position', function ($query) {
                    $query->where('name', 'Chairperson');
                })
                ->first();

            $sacco->status = $chairperson ? "active" : "inactive";
            $sacco->processed = "yes";
            $sacco->save();
        }

        $users = User::all();
        $admin = Admin::user();
        $adminId = $admin->id;
        $userName = $admin->first_name;

        // Filter users based on user type
        $filteredUsers = $users->reject(function ($user) use ($adminId) {
            return $user->id === $adminId && $user->user_type === 'Admin';
        })->reject(function ($user) {
            return in_array($user->user_type, ['4', '5', 'Admin']);
        });

        // Further filter for non-admin role
        $filteredUsers = $filteredUsers->filter(function ($user) {
            return $user->user_type === null || !in_array($user->user_type, ['Admin', '5']);
        });

        // Organization-specific filtering
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
            $logoUrl = $this->getLogoUrl($organization->name);
            $organizationContainer = '<div style="text-align: center; padding-bottom: 25px;"><img src="' . $logoUrl . '" alt="' . $organization->name . '" class="img-fluid rounded-circle" style="max-width: 200px;"></div>';

            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)->pluck('sacco_id')->toArray();
            $totalSaccos = Sacco::whereIn('id', $saccoIds)->count();
            $organisationCount = VslaOrganisation::where('id', $orgIds)->count();
            $totalMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->count();

            $saccoIdsWithPositions = User::whereIn('sacco_id', $saccoIds)
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->whereHas('position', function ($query) {
                    $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                })
                ->pluck('sacco_id')
                ->unique()
                ->toArray();

            $totalAccounts = User::where('user_type', 'Admin')
                ->whereIn('sacco_id', $saccoIdsWithPositions)
                ->count();

            $totalPwdMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->where('pwd', 'yes')->count();
            $villageAgents = User::whereIn('sacco_id', $saccoIds)->where('user_type', '4')->count();
            $youthMembersPercentage = $this->calculatePercentage($filteredUsers->whereIn('sacco_id', $saccoIds), $totalMembers);

            $filteredUsersForBalances = $filteredUsers->whereIn('sacco_id', $saccoIds);
            $pwdUsers = $filteredUsers->where('pwd', 'Yes');
            $pwdMembersCount = $pwdUsers->count();
            $pwdUserIds = $pwdUsers->pluck('id');

            // Get total balances
            $maleBalances = $this->totalBalance($filteredUsersForBalances, 'Male');
            $femaleBalances = $this->totalBalance($filteredUsersForBalances, 'Female');

            // Prepare data for chart
            list($monthYearList, $maleBalanceList, $femaleBalanceList, $totalSavingsList) = $this->prepareChartData($maleBalances, $femaleBalances);

            $topSavingGroups = User::where('user_type', 'Admin')->whereIn('sacco_id', $saccoIds)->get()->sortByDesc('balance')->take(6);

        } else {
            $organizationContainer = '';
            $orgName = 'DigiSave VSLA Platform';
            $totalSaccos = Sacco::count();
            $organisationCount = VslaOrganisation::count();
            $totalMembers = $filteredUsers->count();
            $totalPwdMembers = $filteredUsers->where('pwd', 'yes')->count();
            $villageAgents = User::where('user_type', '4')->count();
            $youthMembersPercentage = $this->calculatePercentage($filteredUsers, $totalMembers);

            $filteredUsersForBalances = $filteredUsers;
            $pwdUsers = $filteredUsers->where('pwd', 'Yes');
            $pwdMembersCount = $pwdUsers->count();
            $pwdUserIds = $pwdUsers->pluck('id');

            // Get total balances
            $maleBalances = $this->totalBalance($filteredUsersForBalances, 'Male');
            $femaleBalances = $this->totalBalance($filteredUsersForBalances, 'Female');

            // Prepare data for chart
            list($monthYearList, $maleBalanceList, $femaleBalanceList, $totalSavingsList) = $this->prepareChartData($maleBalances, $femaleBalances);

            $topSavingGroups = User::where('user_type', 'Admin')->get()->sortByDesc('balance')->take(6);
        }

        $femaleUsers = $filteredUsersForBalances->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();

        $maleUsers = $filteredUsersForBalances->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();

        $youthUsers = $filteredUsersForBalances->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();

        // Calculate loans
        list($totalLoans, $loansDisbursedToWomen, $loansDisbursedToMen) = $this->calculateLoanDisbursements();

        // Calculate loan percentages
        list($percentageLoansWomen, $percentageLoansMen) = $this->calculateLoanPercentages($totalLoans, $loansDisbursedToWomen, $loansDisbursedToMen);

        // Quotes for display
        $quotes = [
            "Empowerment through savings and loans.",
            "Collaboration is key to success.",
            "Building stronger communities together.",
            "Savings groups transform lives.",
            "In unity, there is strength."
        ];

        return $content
            ->header('<div style="text-align: center; color: #066703; font-size: 30px; font-weight: bold; padding-top: 20px;">' . $orgName . '</div>')
            ->body(
                $organizationContainer .
                '<div style="background-color: #F8E5E9; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: #298803;">Welcome back, ' . $userName . '!</h2>
                            <div id="quote-slider" style="margin: 5px 0 0; font-size: 16px; color: #666; height: 20px;">
                                <p>' . $quotes[0] . '</p>
                            </div>
                        </div>
                        <div>
                            <img src="https://www.pngmart.com/files/21/Admin-Profile-PNG-Clipart.png" alt="Welcome Image" style="height: 100px;">
                        </div>
                    </div>
                </div>' . '<div style="text-align: right; margin-bottom: 20px;">
                    <form action="' . route(config('admin.route.prefix') . '.export-data') . '" method="GET">
                        <input type="date" name="start_date" required>
                        <input type="date" name="end_date" required>
                        <button type="submit" class="btn btn-primary">Export Data</button>
                    </form>
                </div>'
                .
                '<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                view('widgets.statistics', [
                    'totalSaccos' => $totalAccounts,
                    'villageAgents' => $villageAgents,
                    'organisationCount' => $organisationCount,
                    'totalMembers' => $totalMembers,
                    'totalAccounts' => $totalAccounts,
                    'totalOrgAdmins' => User::where('user_type', '5')->count(),
                    'totalPwdMembers' => $totalPwdMembers,
                    'youthMembersPercentage' => number_format($youthMembersPercentage, 2),
                ]) .
                view('widgets.card_set', [
                    'femaleMembersCount' => $femaleMembersCount,
                    'femaleTotalBalance' => number_format($femaleBalances->sum('total_balance'), 2),
                    'maleMembersCount' => $maleMembersCount,
                    'maleTotalBalance' => number_format($maleBalances->sum('total_balance'), 2),
                    'youthMembersCount' => $youthMembersCount,
                    'youthTotalBalance' => number_format($youthMembersCount),
                    'pwdMembersCount' => $pwdMembersCount,
                    'pwdTotalBalance' => number_format($pwdMembersCount),
                ]) .
                '<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                view('widgets.category', [
                    'loansDisbursedToWomen' => $loansDisbursedToWomen,
                    'loansDisbursedToMen' => $loansDisbursedToMen,
                    'percentageLoansWomen' => $percentageLoansWomen,
                    'percentageLoansMen' => $percentageLoansMen,
                ]) .
                '</div>' .
                view('widgets.chart_container', [
                    'Female' => $femaleBalanceList,
                    'Male' => $maleBalanceList,
                    'monthYearList' => $monthYearList,
                    'totalSavingsList' => $totalSavingsList,
                ]) .
                '<div class="row" style="padding-top: 35px;">
                    <div class="col-md-6">
                        ' . view('widgets.top_saving_groups', [
                    'topSavingGroups' => $topSavingGroups,
                ]) . '
                    </div>
                    <div class="col-md-6">
                        ' . view('widgets.bar_chart', [
                    'registrationDates' => [],
                    'registrationCounts' => [],
                ]) . '
                    </div>
                </div>'
            );
    }

    private function getLogoUrl($organizationName)
    {
        $logos = [
            'International Institute of Rural Reconstruction (IIRR)' => 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png',
            'Ripple Effect Uganda' => 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png',
        ];

        return $logos[$organizationName] ?? '';
    }

    private function calculatePercentage($filteredUsers, $totalMembers)
    {
        return ($totalMembers > 0) ? $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        })->count() / $totalMembers * 100 : 0;
    }

    private function totalBalance($users, $sex)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        $userIds = $users->pluck('id')->toArray();

        $totalBalances = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('users.sex', $sex)
            ->where('t.type', 'SHARE')
            ->where(function ($query) {
                $query->whereNull('users.user_type')
                      ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('YEAR(t.created_at) as year'), DB::raw('MONTH(t.created_at) as month'), DB::raw('SUM(t.amount) as total_balance'))
            ->groupBy('year', 'month')
            ->orderBy('year', 'month')
            ->get();

        return $totalBalances;
    }

    private function prepareChartData($maleBalances, $femaleBalances)
    {
        $monthYearList = [];
        $maleBalanceList = [];
        $femaleBalanceList = [];
        $totalSavingsList = [];

        foreach ($maleBalances as $balance) {
            $monthYear = Carbon::create($balance->year, $balance->month)->format('F Y');
            $monthYearList[] = $monthYear;
            $maleBalanceList[] = $balance->total_balance;
        }

        foreach ($femaleBalances as $balance) {
            $monthYear = Carbon::create($balance->year, $balance->month)->format('F Y');
            if (!in_array($monthYear, $monthYearList)) {
                $monthYearList[] = $monthYear;
            }
            $femaleBalanceList[] = $balance->total_balance;
        }

        // Combine male and female balances into total savings
        for ($i = 0; $i < count($monthYearList); $i++) {
            $totalSavingsList[] = ($maleBalanceList[$i] ?? 0) + ($femaleBalanceList[$i] ?? 0);
        }

        return [$monthYearList, $maleBalanceList, $femaleBalanceList, $totalSavingsList];
    }

    private function calculateLoanDisbursements()
    {
        $totalLoans = Transaction::where('type', 'LOAN')->count();
        $loansDisbursedToWomen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Female')
            ->count();
        $loansDisbursedToMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', 'Male')
            ->count();

        return [$totalLoans, $loansDisbursedToWomen, $loansDisbursedToMen];
    }

    private function calculateLoanPercentages($totalLoans, $loansDisbursedToWomen, $loansDisbursedToMen)
    {
        $percentageLoansWomen = $totalLoans > 0 ? ($loansDisbursedToWomen / $totalLoans) * 100 : 0;
        $percentageLoansMen = $totalLoans > 0 ? ($loansDisbursedToMen / $totalLoans) * 100 : 0;

        return [$percentageLoansWomen, $percentageLoansMen];
    }
}
