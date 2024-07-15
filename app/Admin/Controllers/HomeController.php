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

class HomeController extends Controller
{

    public function exportData(Request $request)
    {
        // Clear any output buffers to ensure no HTML/JS is included
        while (ob_get_level()) {
            ob_end_clean();
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Validate date inputs
        if (!$startDate || !$endDate) {
            return redirect()->back()->withErrors(['error' => 'Both start and end dates are required.']);
        }

        $admin = Admin::user();
        $adminId = $admin->id;

        // Get all users, then apply filters
        $users = User::all()->reject(function ($user) use ($adminId) {
            return $user->id === $adminId && $user->user_type === 'Admin';
        })->reject(function ($user) {
            return in_array($user->user_type, ['4', '5', 'Admin']);
        });

        // Apply date filter
        $filteredUsers = $users->filter(function ($user) use ($startDate, $endDate) {
            return Carbon::parse($user->created_at)->between($startDate, $endDate);
        });

        // Additional filters based on admin role
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if (!$orgAllocation) {
                Auth::logout();
                $message = "You are not allocated to any organization. Please contact M-Omulimisa Service Help for assistance.";
                Session::flash('warning', $message);
                admin_error($message);
                return redirect('auth/logout');
            }

            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgAllocation->vsla_organisation_id)->pluck('sacco_id')->toArray();
            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);
        }

        // Calculate statistics
        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $maleUsers = $filteredUsers->where('sex', 'Male');
        $youthUsers = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $pwdUsers = $filteredUsers->where('pwd', 'Yes');

        $statistics = [
            'totalAccounts' => $this->getTotalAccounts($filteredUsers),
            'totalMembers' => $filteredUsers->count(),
            'femaleMembersCount' => $femaleUsers->count(),
            'maleMembersCount' => $maleUsers->count(),
            'youthMembersCount' => $youthUsers->count(),
            'pwdMembersCount' => $pwdUsers->count(),
            'femaleTotalBalance' => $this->getTotalBalance($femaleUsers, 'SHARE'),
            'maleTotalBalance' => $this->getTotalBalance($maleUsers, 'SHARE'),
            'youthTotalBalance' => $this->getTotalBalance($youthUsers, 'SHARE'),
            'pwdTotalBalance' => $this->getTotalBalance($pwdUsers, 'SHARE'),
            'totalLoanAmount' => $this->getTotalLoanAmount($filteredUsers, $startDate, $endDate),
            'loanSumForWomen' => $this->getLoanSumForGender($filteredUsers, 'Female', $startDate, $endDate),
            'loanSumForMen' => $this->getLoanSumForGender($filteredUsers, 'Male', $startDate, $endDate),
            'loanSumForYouths' => $this->getLoanSumForYouths($filteredUsers, $startDate, $endDate),
            'pwdTotalLoanBalance' => $this->getTotalLoanBalance($pwdUsers, $startDate, $endDate),
        ];

        return $this->generateCsv($statistics, $startDate, $endDate);
    }

    private function getTotalAccounts($filteredUsers)
    {
        // Calculate total accounts based on the same logic as the dashboard
        // Add your logic here
    }

    private function getTotalBalance($users, $type)
    {
        return Transaction::whereIn('user_id', $users->pluck('id')->toArray())->where('type', $type)->sum('balance');
    }

    private function getTotalLoanAmount($users, $startDate, $endDate)
    {
        return Transaction::where('type', 'LOAN')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');
    }

    private function getLoanSumForGender($users, $gender, $startDate, $endDate)
    {
        return Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.sex', $gender)
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->sum('transactions.amount');
    }

    private function getLoanSumForYouths($users, $startDate, $endDate)
    {
        return Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->whereDate('users.dob', '>', now()->subYears(35))
            ->sum('transactions.amount');
    }

    private function getTotalLoanBalance($users, $startDate, $endDate)
    {
        return Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->where('transactions.type', 'LOAN')
            ->where('users.pwd', 'yes')
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->sum('transactions.amount');
    }

    private function generateCsv($statistics, $startDate, $endDate)
    {
        $fileName = 'export_data_' . $startDate . '_to_' . $endDate . '.csv';
        $filePath = storage_path('exports/' . $fileName);

        if (!file_exists(storage_path('exports'))) {
            mkdir(storage_path('exports'), 0755, true);
        }

        try {
            $file = fopen($filePath, 'w');
            if ($file === false) {
                throw new \Exception('File open failed.');
            }

            fwrite($file, "\xEF\xBB\xBF");

            $data = [
                ['Metric', 'Value'],
                ['Total Number of Groups Registered', $statistics['totalAccounts']],
                ['Total Number of Members', $statistics['totalMembers']],
                ['Number of Members by Gender', ''],
                ['  Female', $statistics['femaleMembersCount']],
                ['  Male', $statistics['maleMembersCount']],
                ['Number of Youth Members', $statistics['youthMembersCount']],
                ['Number of PWDs', $statistics['pwdMembersCount']],
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

            foreach ($data as $row) {
                if (fputcsv($file, array_map('strval', $row)) === false) {
                    throw new \Exception('CSV write failed.');
                }
            }

            fclose($file);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error writing to CSV: ' . $e->getMessage()], 500);
        }

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

            $saccoIds = Sacco::whereIn('id', $saccoIds)
              ->where('status', '!=', 'deleted')->pluck('id')->toArray();

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
            $filteredUsersIds = $filteredUsers->pluck('id');
            $pwdUsers = $filteredUsers->where('pwd', 'Yes');
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

            $pwdTotalLoanBalance = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.pwd', 'yes')
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

            // $pwdTotalLoanBalance = Transaction::whereIn('sacco_id', $saccoIds)->where('type', 'LOAN')
            //     ->whereIn('source_user_id', $pwdUserIds)
            //     ->sum('balance');

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

            $topSavingGroups = User::where('user_type', 'Admin')
                ->whereIn('sacco_id', $saccoIds)
                ->whereHas('sacco', function ($query) {
                    $query->where('status', '!=', 'deleted');
                })
                ->get()
                ->sortByDesc('balance')
                ->take(6);


            // Here

            // Calculate total loans
            $totalLoans = Transaction::whereIn('sacco_id', $saccoIds)
                ->where('type', 'LOAN')
                ->count();

            // Calculate loans given to youths
            $loansGivenToYouths = Transaction::whereIn('sacco_id', $saccoIds)
                ->whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            // Calculate loans given to PWDs
            $loansGivenToPwds = Transaction::whereIn('sacco_id', $saccoIds)
                ->whereIn('source_user_id', $pwdUserIds)
                ->where('type', 'LOAN')
                ->count();

            // Calculate percentages
            $percentageLoansYouths = $totalLoans > 0 ? ($loansGivenToYouths / $totalLoans) * 100 : 0;
            $percentageLoansPwd = $totalLoans > 0 ? ($loansGivenToPwds / $totalLoans) * 100 : 0;

            $youthTotalBalance = number_format(Transaction::whereIn('sacco_id', $saccoIds)
            ->whereIn('source_user_id', $youthIds)
            ->where('type', 'LOAN')
            ->sum('balance'), 2);

            $pwdTotalBalance = number_format(Transaction::whereIn('sacco_id', $saccoIds)
                ->whereIn('source_user_id', $pwdUserIds)
                ->where('type', 'LOAN')
                ->sum('balance'), 2);

            // dd($filteredUsersIds);

            // Fetch saccoIds where the status is deleted
            // Fetch saccoIds where the status is deleted or inactive
// Fetch saccoIds where the status is deleted or inactive
// Fetch saccoIds where the status is deleted or inactive
$deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

// Define the Sacco name you want to filter by
$targetSaccoName = 'Rwencundeezi A Youth Twetungure';

// Fetch individual transactions for the specified sacco name
$individualTransactions = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
    ->join('saccos as s', 'users.sacco_id', '=', 's.id')
    ->where('s.name', $targetSaccoName)
    ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
    ->where('t.type', 'SHARE')
    ->select(
        'users.id',
        DB::raw('CONCAT(users.first_name, " ", users.last_name) as full_name'),
        'users.user_type',
        't.balance',
        's.status as sacco_status',
        's.name as sacco_name'
    )
    ->get()
    ->toArray();

// Display the individual transactions
dd($individualTransactions);

// Fetch aggregated balances for users in the specified sacco name
$usersInTargetSacco = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
    ->join('saccos as s', 'users.sacco_id', '=', 's.id')
    ->where('s.name', $targetSaccoName)
    ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
    ->where('t.type', 'SHARE')
    ->select(
        'users.id',
        DB::raw('CONCAT(users.first_name, " ", users.last_name) as full_name'),
        'users.user_type',
        DB::raw('SUM(t.balance) as total_balance'),
        's.status as sacco_status',
        's.name as sacco_name'
    )
    ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.user_type', 's.status', 's.name')
    ->get()
    ->toArray();

// Display the results
dd($usersInTargetSacco);

// Fetch male users with their balances, sacco status, and sacco name
$maleUsers = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
    ->join('saccos as s', 'users.sacco_id', '=', 's.id')
    ->where('users.sex', 'Male')
    ->whereNotIn('users.id', function ($query) {
        $query->select('id')
            ->from('users')
            ->whereIn('user_type', ['Admin', '5']);
    })
    ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
    ->where('t.type', 'SHARE')
    ->select('users.id', 'users.name', DB::raw('SUM(t.balance) as total_balance'), 's.status as sacco_status', 's.name as sacco_name')
    ->groupBy('users.id', 'users.name', 's.status', 's.name')
    ->get()
    ->toArray();

// Fetch female users with their balances, sacco status, and sacco name
$femaleUsers = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
    ->join('saccos as s', 'users.sacco_id', '=', 's.id')
    ->where('users.sex', 'Female')
    ->whereNotIn('users.id', function ($query) {
        $query->select('id')
            ->from('users')
            ->whereIn('user_type', ['Admin', '5']);
    })
    ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
    ->where('t.type', 'SHARE')
    ->select('users.id', 'users.name', DB::raw('SUM(t.balance) as total_balance'), 's.status as sacco_status', 's.name as sacco_name')
    ->groupBy('users.id', 'users.name', 's.status', 's.name')
    ->get()
    ->toArray();

// Display the results
dd($maleUsers, $femaleUsers);

            // $topSavingGroups = User::where('user_type', 'Admin')->whereIn('sacco_id', $saccoIds)->get()->sortByDesc('balance')->take(6);
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
            $pwdUsers = $filteredUsers->where('pwd', 'Yes');
            $pwdMembersCount = $pwdUsers->count();
            $pwdUserIds = $pwdUsers->pluck('id');

            $pwdBalances = Transaction::where('type', 'SHARE')
                ->whereIn('user_id', $pwdUserIds)
                ->select('user_id', DB::raw('SUM(balance) as total_balance'))
                ->groupBy('user_id')
                ->get();

            // Formatting the output
            $formattedBalances = $pwdBalances->map(function ($balance) {
                return [
                    'user_id' => $balance->user_id,
                    'total_balance' => $balance->total_balance,
                ];
            });

            // die($formattedBalances);

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

            $pwdTotalLoanBalance = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->where('users.pwd', 'yes')
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

            // $pwdTotalLoanBalance = Transaction::where('type', 'LOAN')
            //     ->whereIn('source_user_id', $pwdUserIds)
            //     ->sum('balance');

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

            //Here

            // Calculate total loans
            $totalLoans = Transaction::where('type', 'LOAN')
                ->count();

            // Calculate loans given to youths
            $loansGivenToYouths = Transaction::whereIn('source_user_id', $youthIds)
                ->where('type', 'LOAN')
                ->count();

            // Calculate loans given to PWDs
            $loansGivenToPwds = Transaction::whereIn('source_user_id', $pwdUserIds)
                ->where('type', 'LOAN')
                ->count();

            // Calculate percentages
            $percentageLoansYouths = $totalLoans > 0 ? ($loansGivenToYouths / $totalLoans) * 100 : 0;
            $percentageLoansPwd = $totalLoans > 0 ? ($loansGivenToPwds / $totalLoans) * 100 : 0;

            $youthTotalBalance = number_format(Transaction::whereIn('source_user_id', $youthIds)
                -> where('type', 'LOAN')
            ->sum('balance'), 2);

            $pwdTotalBalance = number_format(Transaction::whereIn('source_user_id', $pwdUserIds)
                ->where('type', 'LOAN')
                ->sum('balance'), 2);

            $filteredUsersIds = $filteredUsers->pluck('id')->toArray();

            // dd($filteredUsersIds);

            $maleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->where('users.sex', 'Male')
                ->whereNotIn('users.id', function ($query) {
                    $query->select('id')
                        ->from('users')
                        ->whereIn('user_type', ['Admin', '5']);
                })
                ->where('t.type', 'SHARE')
                ->where('users.sacco_join_status', 'Approved')
                ->sum('t.balance');

            $femaleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->where('users.sex', 'Female')
                ->whereNotIn('users.id', function ($query) {
                    $query->select('id')
                        ->from('users')
                        ->whereIn('user_type', ['Admin', '5']);
                })
                ->where('t.type', 'SHARE')
                ->where('users.sacco_join_status', 'Approved')
                ->sum('t.balance');
        }
            $femaleUsers = $filteredUsersForBalances->where('sex', 'Female');
            $femaleMembersCount = $femaleUsers->count();
        // $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);

        // dd($femaleTotalBalance);

        $maleUsers = $filteredUsersForBalances->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        // $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);

        $youthUsers = $filteredUsersForBalances->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();
        // $youthTotalBalance = number_format($youthUsers->sum('balance'), 2);

        $totalLoans = $loansDisbursedToWomen + $loansDisbursedToMen;
        $percentageLoansWomen = $totalLoans > 0 ? ($loansDisbursedToWomen / $totalLoans) * 100 : 0;
        $percentageLoansMen = $totalLoans > 0 ? ($loansDisbursedToMen / $totalLoans) * 100 : 0;
        // $percentageLoansYouths = $totalLoans > 0 ? ($loansDisbursedToYouths / $totalLoans) * 100 : 0;
        // $percentageLoansPwd = $totalLoans > 0 ? ($pwdTotalLoanCount / $totalLoans) * 100 : 0;

        $totalLoanSum = $loanSumForWomen + $loanSumForMen;
        $percentageLoanSumWomen = $totalLoanSum > 0 ? ($loanSumForWomen / $totalLoanSum) * 100 : 0;
        $percentageLoanSumMen = $totalLoanSum > 0 ? ($loanSumForMen / $totalLoanSum) * 100 : 0;
        // $percentageLoanSumYouths = $totalLoanSum > 0 ? ($loanSumForYouths / $totalLoanSum) * 100 : 0;

        $quotes = [
            "Empowerment through savings and loans.",
            "Collaboration is key to success.",
            "Building stronger communities together.",
            "Savings groups transform lives.",
            "In unity, there is strength."
        ];

        $totalLoanAmount = $loanSumForWomen + $loanSumForMen + $loanSumForYouths;

        return $content
            ->header('<div style="text-align: center; color: #066703; font-size: 30px; font-weight: bold; padding-top: 20px;">' . $orgName . '</div>')
            ->body(
                $organizationContainer .
                    '<div style="background-color: #F8E5E9; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: #298803;">Welcome back, ' . $userName .
                    '!</h2>
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
                        'totalOrgAdmins' => $totalOrgAdmins,
                        'totalPwdMembers' => $pwdMembersCount,
                        'youthMembersPercentage' => number_format($youthMembersPercentage, 2),
                    ]) .
                    view('widgets.card_set', [
                        'femaleMembersCount' => $femaleMembersCount,
                        'femaleTotalBalance' => number_format($femaleTotalBalance, 2),
                        'maleMembersCount' => $maleMembersCount,
                        'maleTotalBalance' => number_format($maleTotalBalance, 2),
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
                        'percentageLoansPwd' => $percentageLoansPwd,
                        'percentageLoanSumWomen' => $percentageLoanSumWomen,
                        'percentageLoanSumMen' => $percentageLoanSumMen,
                        // 'percentageLoanSumYouths' => $percentageLoanSumYouths,
                        'pwdTotalLoanBalance' => $pwdTotalLoanBalance,
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
?>


<script>
    $(document).ready(function() {
        const quotes = [
            "Empowerment through savings and loans.",
            "Collaboration is key to success.",
            "Building stronger communities together.",
            "Savings groups transform lives.",
            "In unity, there is strength."
        ];

        let quoteIndex = 0;

        function showNextQuote() {
            quoteIndex = (quoteIndex + 1) % quotes.length;
            $('#quote-slider p').fadeOut(500, function() {
                $(this).text(quotes[quoteIndex]).fadeIn(500);
            });
        }

        setInterval(showNextQuote, 3000); // Change quote every 3 seconds
    });
</script>
