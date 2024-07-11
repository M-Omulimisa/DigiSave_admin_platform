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
use App\Models\VslaOrganisationSacco;
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
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{

    public function exportData(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // Validate date inputs
    if (!$startDate || !$endDate) {
        return redirect()->back()->withErrors(['error' => 'Both start and end dates are required.']);
    }

    // Retrieve the data from session
    $statistics = Session::get('dashboard_data');

    if (!$statistics) {
        return redirect()->back()->withErrors(['error' => 'No data available for export.']);
    }

    // Prepare data for export
    $data = [
        ['Metric', 'Value'],
        ['Total Number of Groups Registered', $statistics['totalAccounts']],
        ['Total Number of Members', $statistics['totalMembers']],
        ['Number of Members by Gender', ''],
        ['  Female', $statistics['femaleMembersCount']],
        ['  Male', $statistics['maleMembersCount']],
        ['Number of Youth Members', $statistics['youthMembersCount']],
        ['Number of PWDs', $statistics['pwdMembersCount']],
        // ['Total Savings', $statistics['totalSavings']],
        ['Savings by Gender', ''],
        ['  Female', $statistics['femaleTotalBalance']],
        ['  Male', $statistics['maleTotalBalance']],
        ['Savings by Youth', $statistics['youthTotalBalance']],
        ['Savings by PWDs', $statistics['pwdTotalBalance']],
        ['Total Loans', $statistics['totalLoanAmount']],
        ['Loans by Gender', ''],
        ['  Female', $statistics['loanSumForWomen']],
        ['  Male', $statistics['loanSumForMen']],
        ['Loans by Youth', $statistics['loanSumForYouths']],
        ['Loans by PWDs', $statistics['pwdTotalLoanBalance']],
    ];

    $fileName = 'export_data_' . $startDate . '_to_' . $endDate . '.csv';
    $filePath = storage_path('exports/' . $fileName);

    // Ensure the directory exists
    if (!file_exists(storage_path('exports'))) {
        mkdir(storage_path('exports'), 0755, true);
    }

    // Write data to CSV
    try {
        $file = fopen($filePath, 'w');
        if ($file === false) {
            throw new Exception('File open failed.');
        }

        // Write UTF-8 BOM for proper encoding in Excel
        fwrite($file, "\xEF\xBB\xBF");

        foreach ($data as $row) {
            if (fputcsv($file, array_map('strval', $row)) === false) {
                throw new Exception('CSV write failed.');
            }
        }

        fclose($file);
    } catch (Exception $e) {
        return response()->json(['error' => 'Error writing to CSV: ' . $e->getMessage()], 500);
    }

    // Return the CSV file as a download response
    return response()->download($filePath, $fileName, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ])->deleteFileAfterSend(true);
}


public function index(Content $content)
{
    $startDate = request()->input('start_date');
    $endDate = request()->input('end_date');

    if (!$startDate || !$endDate) {
        $startDate = Carbon::now()->subMonth();
        $endDate = Carbon::now();
    } else {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();
    }

    foreach (Sacco::where("processed", "no")->get() as $sacco) {
        $chairperson = User::where('sacco_id', $sacco->id)
            ->whereHas('position', function ($query) {
                $query->where('name', 'Chairperson');
            })
            ->first();

        $sacco->status = $chairperson ? "active" : "inactive";
        $sacco->processed = "yes";
        $sacco->save();
    }

    $users = User::whereBetween('created_at', [$startDate, $endDate])->get();
    $admin = Admin::user();
    $adminId = $admin->id;
    $userName = $admin->first_name;

    $totalAccounts = Sacco::whereHas('users', function ($query) use ($startDate, $endDate) {
        $query->whereHas('position', function ($query) {
            $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
        })->whereNotNull('phone_number')
            ->whereNotNull('name')
            ->whereBetween('created_at', [$startDate, $endDate]);
    })->count();

    $totalOrgAdmins = User::where('user_type', '5')->whereBetween('created_at', [$startDate, $endDate])->count();

    $filteredUsers = $users->reject(function ($user) use ($adminId) {
        return $user->id === $adminId && $user->user_type === 'Admin';
    })->reject(function ($user) {
        return in_array($user->user_type, ['4', '5']);
    })->filter(function ($user) {
        return is_null($user->user_type) || !in_array($user->user_type, ['Admin', '5']);
    });

    // Fetch organization details if the user is not a global admin
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
        $logoUrl = $this->getOrganizationLogo($organization->name);
        $organizationContainer = '<div style="text-align: center; padding-bottom: 25px;"><img src="' . $logoUrl . '" alt="' . $organization->name . '" class="img-fluid rounded-circle" style="max-width: 200px;"></div>';

        $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)->pluck('sacco_id')->toArray();
        $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgIds)->pluck('vsla_organisation_id')->toArray();
        $totalOrgAdmins = count($OrgAdmins);

        $totalSaccos = Sacco::whereIn('id', $saccoIds)->count();
        $organisationCount = VslaOrganisation::where('id', $orgIds)->count();
        $totalMembers = $filteredUsers->whereIn('sacco_id', $saccoIds)->count();

        $saccoIdsWithPositions = User::whereIn('sacco_id', $saccoIds)
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
        $youthMembersPercentage = $this->calculateYouthMembersPercentage($filteredUsers, $saccoIds);

        $filteredUsersForBalances = $filteredUsers->whereIn('sacco_id', $saccoIds);
        $pwdUsers = $filteredUsersForBalances->where('pwd', 'Yes');
        $pwdMembersCount = $pwdUsers->count();
        $pwdUserIds = $pwdUsers->pluck('id');

        $pwdTotalBalance = $this->calculateTotalBalance(Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'SHARE')->whereIn('user_id', $pwdUserIds));
        $loansDisbursedToWomen = $this->calculateLoanCount(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Female'));
        $loansDisbursedToMen = $this->calculateLoanCount(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Male'));

        $youthIds = User::whereIn('sacco_id', $saccoIds)->whereDate('dob', '>', now()->subYears(35))->pluck('id');
        $loansDisbursedToYouths = $this->calculateLoanCount(Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $youthIds)->where('type', 'LOAN'));
        $loanSumForWomen = $this->calculateLoanSum(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Female'));
        $loanSumForMen = $this->calculateLoanSum(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Male'));
        $loanSumForYouths = $this->calculateLoanSum(Transaction::whereIn('sacco_id', $saccoIds)->whereIn('source_user_id', $youthIds)->where('type', 'LOAN'));

        $pwdTotalLoanCount = $this->calculateLoanCount(Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'LOAN')->whereIn('source_user_id', $pwdUserIds));
        $pwdTotalLoanBalance = $this->calculateTotalBalance(Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'LOAN')->whereIn('source_user_id', $pwdUserIds));

        $totalLoanAmount = $this->calculateTotalAmount(Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->pluck('id'))->where('type', 'LOAN'));
        $totalLoanBalance = $this->calculateTotalBalance(Transaction::whereIn('sacco_id', $saccoIds)->whereIn('user_id', $filteredUsers->pluck('id'))->where('type', 'LOAN'));

        $transactions = Transaction::whereIn('sacco_id', $saccoIds)->whereBetween('created_at', [$startDate, $endDate])->get();
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

        $userRegistrations = $users->whereIn('sacco_id', $saccoIds)->where('user_type', '!=', 'Admin')->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y-m');
        });

        $registrationDates = $userRegistrations->keys()->toArray();
        $registrationCounts = $userRegistrations->map(function ($item) {
            return count($item);
        })->values()->toArray();

        $topSavingGroups = User::where('user_type', 'Admin')->whereIn('sacco_id', $saccoIds)->get()->sortByDesc('balance')->take(6);
    } else {
        $organizationContainer = '';
        $orgName = 'DigiSave VSLA Platform';
        $totalSaccos = Sacco::count();
        // dd($totalSaccos);
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

        $pwdTotalBalance = $this->calculateTotalBalance(Transaction::where('type', 'SHARE')->whereIn('user_id', $pwdUserIds));
        $loansDisbursedToWomen = $this->calculateLoanCount(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Female'));
        $loansDisbursedToMen = $this->calculateLoanCount(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Male'));

        $youthIds = User::whereDate('dob', '>', now()->subYears(35))->pluck('id');
        $loansDisbursedToYouths = $this->calculateLoanCount(Transaction::whereIn('user_id', $youthIds)->where('type', 'LOAN'));
        $loanSumForWomen = $this->calculateLoanSum(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Female'));
        $loanSumForMen = $this->calculateLoanSum(Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')->where('transactions.type', 'LOAN')->where('users.sex', 'Male'));
        $loanSumForYouths = $this->calculateLoanSum(Transaction::whereIn('source_user_id', $youthIds)->where('type', 'LOAN'));

        $pwdTotalLoanCount = $this->calculateLoanCount(Transaction::where('type', 'LOAN')->whereIn('source_user_id', $pwdUserIds));
        $pwdTotalLoanBalance = $this->calculateTotalBalance(Transaction::where('type', 'LOAN')->whereIn('source_user_id', $pwdUserIds));

        $totalLoanAmount = $this->calculateTotalAmount(Transaction::whereIn('user_id', $filteredUsers->pluck('id'))->where('type', 'LOAN'));
        $totalLoanBalance = $this->calculateTotalBalance(Transaction::whereIn('user_id', $filteredUsers->pluck('id'))->where('type', 'LOAN'));

        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->get();
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

        $userRegistrations = $users->where('user_type', '!=', 'Admin')->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y-m');
        });

        $registrationDates = $userRegistrations->keys()->toArray();
        $registrationCounts = $userRegistrations->map(function ($item) {
            return count($item);
        })->values()->toArray();

        $topSavingGroups = User::where('user_type', 'Admin')->get()->sortByDesc('balance')->take(6);
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

    $totalLoans = $loansDisbursedToWomen + $loansDisbursedToMen + $loansDisbursedToYouths;
    $percentageLoansWomen = $totalLoans > 0 ? ($loansDisbursedToWomen / $totalLoans) * 100 : 0;
    $percentageLoansMen = $totalLoans > 0 ? ($loansDisbursedToMen / $totalLoans) * 100 : 0;
    $percentageLoansYouths = $totalLoans > 0 ? ($loansDisbursedToYouths / $totalLoans) * 100 : 0;
    $percentageLoansPwd = $totalLoans > 0 ? ($pwdTotalLoanCount / $totalLoans) * 100 : 0;

    $totalLoanSum = $loanSumForWomen + $loanSumForMen + $loanSumForYouths;
    $percentageLoanSumWomen = $totalLoanSum > 0 ? ($loanSumForWomen / $totalLoanSum) * 100 : 0;
    $percentageLoanSumMen = $totalLoanSum > 0 ? ($loanSumForMen / $totalLoanSum) * 100 : 0;
    $percentageLoanSumYouths = $totalLoanSum > 0 ? ($loanSumForYouths / $totalLoanSum) * 100 : 0;

    $quotes = [
        "Empowerment through savings and loans.",
        "Collaboration is key to success.",
        "Building stronger communities together.",
        "Savings groups transform lives.",
        "In unity, there is strength."
    ];

    $data = [
        'totalAccounts' => $totalAccounts,
        'totalOrgAdmins' => $totalOrgAdmins,
        'totalSaccos' => $totalAccounts,
        'organisationCount' => $organisationCount,
        'totalMembers' => $totalMembers,
        'totalPwdMembers' => $pwdMembersCount,
        'villageAgents' => $villageAgents,
        'youthMembersPercentage' => $youthMembersPercentage,
        'femaleMembersCount' => $femaleMembersCount,
        'femaleTotalBalance' => $femaleTotalBalance,
        'maleMembersCount' => $maleMembersCount,
        'maleTotalBalance' => $maleTotalBalance,
        'youthMembersCount' => $youthMembersCount,
        'youthTotalBalance' => $youthTotalBalance,
        'pwdMembersCount' => $pwdMembersCount,
        'pwdTotalBalance' => $pwdTotalBalance,
        'loansDisbursedToWomen' => $loansDisbursedToWomen,
        'loansDisbursedToMen' => $loansDisbursedToMen,
        'loansDisbursedToYouths' => $loansDisbursedToYouths,
        'loanSumForWomen' => abs($loanSumForWomen),
        'loanSumForMen' => abs($loanSumForMen),
        'loanSumForYouths' => $loanSumForYouths,
        'pwdTotalLoanCount' => $pwdTotalLoanCount,
        'pwdTotalLoanBalance' => $pwdTotalLoanBalance,
        'totalLoanAmount' => $totalLoanAmount,
        'totalLoanBalance' => $totalLoanBalance,
        'monthYearList' => $monthYearList,
        'totalSavingsList' => $totalSavingsList,
        'topSavingGroups' => $topSavingGroups,
        'registrationDates' => $registrationDates,
        'registrationCounts' => $registrationCounts,
        'orgName' => $orgName,
        'organizationContainer' => $organizationContainer,
        'userName' => $userName,
        'quotes' => $quotes,
    ];

    // Store the data in the session to make it accessible for exportData method
    Session::put('dashboard_data', $data);

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
                </div>' .
                '<div style="text-align: right; margin-bottom: 20px;">
                    <form action="' . route(config('admin.route.prefix') . '.home') . '" method="GET">
                        <input type="date" name="start_date" value="' . $startDate->toDateString() . '" required>
                        <input type="date" name="end_date" value="' . $endDate->toDateString() . '" required>
                        <button type="submit" class="btn btn-primary">Filter Data</button>
                    </form>
                </div>' .
                '<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                    view('widgets.statistics', [
                        'totalSaccos' => $totalAccounts,
                        'villageAgents' => $villageAgents,
                        'organisationCount' => $organisationCount,
                        'totalMembers' => $totalMembers,
                        'totalAccounts' => $totalAccounts,
                        'totalOrgAdmins' => $totalOrgAdmins,
                        'totalPwdMembers' => $pwdMembersCount,
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
                '<div style="background-color: #E9F9E9; padding: 10px; padding-top: 5px; border-radius: 5px;">' .
                    view('widgets.category', [
                        'loansDisbursedToWomen' => $loansDisbursedToWomen,
                        'loansDisbursedToMen' => $loansDisbursedToMen,
                        'loansDisbursedToYouths' => $loansDisbursedToYouths,
                        'loanSumForWomen' => $loanSumForWomen,
                        'loanSumForMen' => $loanSumForMen,
                        'loanSumForYouths' => $loanSumForYouths,
                        'pwdTotalLoanCount' => $pwdTotalLoanCount,
                        'percentageLoansWomen' => $percentageLoansWomen,
                        'percentageLoansMen' => $percentageLoansMen,
                        'percentageLoansYouths' => $percentageLoansYouths,
                        'percentageLoanSumWomen' => $percentageLoanSumWomen,
                        'percentageLoanSumMen' => $percentageLoanSumMen,
                        'percentageLoanSumYouths' => $percentageLoanSumYouths,
                    ]) .
                '</div>' .
                view('widgets.chart_container', [
                    'Female' => $femaleTotalBalance,
                    'Male' => $maleTotalBalance,
                    'monthYearList' => $monthYearList,
                    'totalSavingsList' => $totalSavingsList,
                ]) .
                '<div class="row" style="padding-top: 35px;">
                    <div class="col-md-6">' .
                        view('widgets.top_saving_groups', [
                            'topSavingGroups' => $topSavingGroups,
                        ]) . '
                    </div>
                    <div class="col-md-6">' .
                        view('widgets.bar_chart', [
                            'registrationDates' => $registrationDates,
                            'registrationCounts' => $registrationCounts,
                        ]) . '
                    </div>
                </div>'
        );
}

private function calculateYouthMembersPercentage($users, $saccoIds = null)
{
    if ($saccoIds) {
        $filteredUsers = $users->whereIn('sacco_id', $saccoIds);
    } else {
        $filteredUsers = $users;
    }

    return ($filteredUsers->count() > 0) ? $filteredUsers->filter(function ($user) {
        return Carbon::parse($user->dob)->age < 35;
    })->count() / $filteredUsers->count() * 100 : 0;
}

private function calculateTotalBalance($transactions)
{
    return number_format($transactions->sum('balance'), 2);
}

private function calculateLoanCount($transactions)
{
    return $transactions->count();
}

private function calculateLoanSum($transactions)
{
    return $transactions->sum('amount');
}

private function calculateTotalAmount($transactions)
{
    return $transactions->sum('amount');
}

private function getOrganizationLogo($organizationName)
{
    $logos = [
        'International Institute of Rural Reconstruction (IIRR)' => 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png',
        'Ripple Effect Uganda' => 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png',
    ];

    return $logos[$organizationName] ?? '';
}
}
