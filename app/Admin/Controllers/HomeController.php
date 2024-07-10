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

    // Fetch data based on date range
    $groupsOnboarded = Sacco::whereBetween('created_at', [$startDate, $endDate])->count();
    $totalMembers = User::whereBetween('created_at', [$startDate, $endDate])->count();
    $membersByGender = User::select('sex', DB::raw('count(*) as total'))
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('sex')
        ->get();
    $youthMembers = User::whereDate('dob', '>', now()->subYears(35))
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();
    $pwds = User::where('pwd', 'yes')->whereBetween('created_at', [$startDate, $endDate])->count();

    $totalSavings = Transaction::where('type', 'SHARE')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');
    $savingsByGender = Transaction::select('users.sex', DB::raw('sum(transactions.amount) as total'))
        ->join('users', 'transactions.user_id', '=', 'users.id')
        ->where('transactions.type', 'SHARE')
        ->whereBetween('transactions.created_at', [$startDate, $endDate])
        ->groupBy('users.sex')
        ->get();
    $savingsByYouth = Transaction::whereHas('user', function($query) {
            $query->whereDate('dob', '>', now()->subYears(35));
        })
        ->where('type', 'SHARE')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');
    $savingsByPwd = Transaction::whereHas('user', function($query) {
            $query->where('pwd', 'yes');
        })
        ->where('type', 'SHARE')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');

    $totalLoans = Transaction::where('type', 'LOAN')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');
    $loansByGender = Transaction::select('users.sex', DB::raw('sum(transactions.amount) as total'))
        ->join('users', 'transactions.user_id', '=', 'users.id')
        ->where('transactions.type', 'LOAN')
        ->whereBetween('transactions.created_at', [$startDate, $endDate])
        ->groupBy('users.sex')
        ->get();
    $loansByYouth = Transaction::whereHas('user', function($query) {
            $query->whereDate('dob', '>', now()->subYears(35));
        })
        ->where('type', 'LOAN')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');
    $loansByPwd = Transaction::whereHas('user', function($query) {
            $query->where('pwd', 'yes');
        })
        ->where('type', 'LOAN')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('amount');

    // Prepare data for export
    $data = [
        ['Metric', 'Value'],
        ['Total Number of Groups Registered', $groupsOnboarded],
        ['Total Number of Members', $totalMembers],
        ['Number of Members by Gender', ''],
    ];

    foreach ($membersByGender as $member) {
        $data[] = ['  ' . $member->sex, $member->total];
    }

    $data[] = ['Number of Youth Members', $youthMembers];
    $data[] = ['Number of PWDs', $pwds];
    $data[] = ['Total Savings', $totalSavings];
    $data[] = ['Savings by Gender', ''];

    foreach ($savingsByGender as $saving) {
        $data[] = ['  ' . $saving->sex, $saving->total];
    }

    $data[] = ['Savings by Youth', $savingsByYouth];
    $data[] = ['Savings by PWDs', $savingsByPwd];
    $data[] = ['Total Loans', $totalLoans];
    $data[] = ['Loans by Gender', ''];

    foreach ($loansByGender as $loan) {
        $data[] = ['  ' . $loan->sex, $loan->total];
    }

    $data[] = ['Loans by Youth', $loansByYouth];
    $data[] = ['Loans by PWDs', $loansByPwd];

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
        foreach (Sacco::where(["processed" => "no"])->get() as $key => $sacco) {
            $chairperson = User::where('sacco_id', $sacco->id)
                ->whereHas('position', function ($query) {
                    $query->where('name', 'Chairperson');
                })
                ->first();

            if ($chairperson == null) {
                $sacco->status = "inactive";
            } else {
                $sacco->status = "active";
            }
            $sacco->processed = "yes";
            $sacco->save();
        }
        $users = User::all();
        $admin = Admin::user();
        $adminId = $admin->id;
        $userName = $admin->first_name;

        $totalAccounts = Sacco::whereHas('users', function ($query) {
            $query->whereHas('position', function ($query) {
                $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
            })->whereNotNull('phone_number')
                ->whereNotNull('name');
        })->count();

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
            $logoUrl = '';
            if ($organization->name === 'International Institute of Rural Reconstruction (IIRR)') {
                $logoUrl = 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png';
            } elseif ($organization->name === 'Ripple Effect Uganda') {
                $logoUrl = 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png';
            }
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

            $loansDisbursedToWomen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->count();

            $loansDisbursedToMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->count();

            // Get the IDs of youth users
            $youthIds = User::whereIn('sacco_id', $saccoIds)
                ->whereDate('dob', '>', now()->subYears(35))
                ->pluck('id');

            // Count the number of youths
            $youthCount = $youthIds->count();

            // dd($youthCount);

            // Get the IDs of youth users
            $youthIds = User::whereIn('sacco_id', $saccoIds)
                ->whereDate('dob', '>', now()->subYears(35))
                ->pluck('id');

            // Count loans disbursed to youths
            $loansDisbursedToYouths = Transaction::whereIn('sacco_id', $saccoIds)
                ->whereIn('user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            $loanSumForWomen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $loanSumForMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');



            // Count loans disbursed to youths
            $loansDisbursedToYouths = Transaction::whereIn('sacco_id', $saccoIds)->whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            // Sum the loan amounts disbursed to youths
            $loanSumForYouths = Transaction::whereIn('sacco_id', $saccoIds)
                ->whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->sum('amount');

            $pwdTotalLoanCount = Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'LOAN')
                ->whereIn('source_user_id', $pwdUserIds)
                ->count();

            $pwdTotalLoanBalance = Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'LOAN')
                ->whereIn('source_user_id', $pwdUserIds)
                ->sum('balance');

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

            $loansDisbursedToWomen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->count();

            $loansDisbursedToMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->count();

            // Get the IDs of youth users
            $youthIds = User::whereDate('dob', '>', now()->subYears(35))
                ->pluck('id');

            // Count the number of youths
            $youthCount = $youthIds->count();

            // dd($youthCount);


            $loansDisbursedToYouths = Transaction::whereIn('user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            $loanSumForWomen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $loanSumForMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Count loans disbursed to youths
            $loansDisbursedToYouths = Transaction::whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            // Sum the loan amounts disbursed to youths
            $loanSumForYouths = Transaction::whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->sum('amount');
            $totalLoanAmount = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                ->where('type', 'LOAN')
                ->sum('amount');

            $totalLoanBalance = Transaction::whereIn('user_id', $filteredUsers->pluck('id'))
                ->where('type', 'LOAN')
                ->sum('balance');

            $pwdTotalLoanCount = Transaction::where('type', 'LOAN')
                ->whereIn('source_user_id', $pwdUserIds)
                ->count();

            $pwdTotalLoanBalance = Transaction::where('type', 'LOAN')
                ->whereIn('source_user_id', $pwdUserIds)
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
                    <form action="' . route(config('admin.route.prefix') . '.export-data') . '" method="GET">
                        <input type="date" name="start_date" required>
                        <input type="date" name="end_date" required>
                        <button type="submit" class="btn btn-primary">Export Data</button>
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
                        // 'percentageLoansPwd' => $percentageLoansPwd,
                        'percentageLoanSumWomen' => $percentageLoanSumWomen,
                        'percentageLoanSumMen' => $percentageLoanSumMen,
                        'percentageLoanSumYouths' => $percentageLoanSumYouths,
                        // 'pwdTotalLoanBalance' => $pwdTotalLoanBalance
                    ]) .
                    '</div>' .
                    view('widgets.chart_container', [
                        'Female' => $femaleTotalBalance,
                        'Male' => $maleTotalBalance,
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
                        'registrationDates' => $registrationDates,
                        'registrationCounts' => $registrationCounts,
                    ]) . '
                        </div>
                    </div>'
            );
    }
}
