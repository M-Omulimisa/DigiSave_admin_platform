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

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        // Check if dates represent "from system start to current date"
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        // Validate date inputs
        if (!$startDate || !$endDate) {
            return redirect()->back()->withErrors(['error' => 'Both start and end dates are required.']);
        }

        $admin = Admin::user();
        $adminId = $admin->id;

        $users = User::all();

        // Get IDs of deleted or inactive Saccos
        $deletedOrInactiveSaccoIds = Sacco::where('status', '!=', 'ACTIVE')
            ->pluck('id')
            ->toArray();

        // Apply user type restrictions but not date filter when using all-time data
        $filteredUsers = $users->filter(function ($user) use ($startDate, $endDate, $adminId, $isAllTimeData) {
            if ($isAllTimeData) {
                // For all-time data, don't filter by date, just by user type
                return $user->id !== $adminId &&
                       !in_array($user->user_type, ['Admin', '4', '5']);
            } else {
                // For specific date ranges, filter by date and user type
                $createdAt = Carbon::parse($user->created_at);
                return $createdAt->between($startDate, $endDate) &&
                    $user->id !== $adminId &&
                    !in_array($user->user_type, ['Admin', '4', '5']);
            }
        });

        $filteredUserIds = $filteredUsers->pluck('id');

        // Additional filters based on admin role
        $userIsAdmin = false;
        if ($admin && method_exists($admin, 'isRole')) {
            $userIsAdmin = $admin->isRole('admin') || $admin->isRole('administrator');
        }

        if (!$userIsAdmin) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if (!$orgAllocation) {
                Auth::logout();
                $message = "You are not allocated to any organization. Please contact M-Omulimisa Service Help for assistance.";
                Session::flash('warning', $message);
                admin_error($message);
                return redirect('auth/logout');
            }

            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgAllocation->vsla_organisation_id)
                ->pluck('sacco_id')->toArray();
            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);

            $genderDistribution = User::whereIn('sacco_id', $saccoIds)
                ->select('sex', DB::raw('count(*) as count'))
                ->groupBy('sex')
                ->get()
                ->map(function ($item) {
                    return [
                        'sex' => $item->sex ?? 'Undefined',
                        'count' => $item->count,
                    ];
                });

            // Filter transactions for male and female users
            $maleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $femaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            // Add refugee sum calculations
            $refugeMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $refugeFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // Add PWD sum calculations
            $pwdMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');
        } else {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if (!$orgAllocation) {
                Auth::logout();
                $message = "You are not allocated to any organization. Please contact M-Omulimisa Service Help for assistance.";
                Session::flash('warning', $message);
                admin_error($message);
                return redirect('auth/logout');
            }

            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgAllocation->vsla_organisation_id)
                ->pluck('sacco_id')->toArray();
            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);

            $genderDistribution = User::whereIn('sacco_id', $saccoIds)
                ->select('sex', DB::raw('count(*) as count'))
                ->groupBy('sex')
                ->get()
                ->map(function ($item) {
                    return [
                        'sex' => $item->sex ?? 'Undefined',
                        'count' => $item->count,
                    ];
                });

            // dd('Gender distribution:', $genderDistribution->toArray());

            $filteredUserIds = $filteredUsers->pluck('id');
            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            // Retrieve and sum up transactions for filtered users and specified SACCOs within the date range
            $totalShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount'); // Sum up the transaction amounts

            // dd('Total share sum for filtered users in specified SACCOs:', $totalShareSum);

            // Total share sum for male users
            $maleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.sex', 'Male') // Filter for male users
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $femaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.sex', 'Female') // Filter for female users
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $undefinedGenderSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->whereNull('users.sex')
                ->orWhere('users.sex', '') // In case Undefined is stored explicitly
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $refugeMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $refugeFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $pwdMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.pwd', 'yes')
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $pwdFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'SHARE')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.pwd', 'yes')
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $refugeMaleLoanSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'LOAN')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $refugeFemaleLoanSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'LOAN')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $pwdMaleLoanSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'LOAN')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.pwd', 'yes')
                ->where('users.sex', 'Male')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            $pwdFemaleLoanSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join(
                    'saccos',
                    'users.sacco_id',
                    '=',
                    'saccos.id'
                )
                ->where('transactions.type', 'LOAN')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->whereBetween('transactions.created_at', [$startDate, $endDate])
                ->where('users.pwd', 'yes')
                ->where('users.sex', 'Female')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->sum('transactions.amount');

            // dd('Total share sum for filtered users in specified SACCOs:', $totalShareSum);
        }

        // dd('Filtered users count:', $filteredUsers->count(), 'Total users count:', $users->count());

        // Calculate statistics
        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $maleUsers = $filteredUsers->where('sex', 'Male');
        $refuges = $filteredUsers->where('refugee_status', 'yes');
        $youthUsers = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $pwdUsers = $filteredUsers->where('pwd', 'Yes');

        // Define variables to prevent undefined variable errors
        $refugeMaleShareSum = 0;
        $refugeFemaleShareSum = 0;
        $pwdMaleShareSum = 0;
        $pwdFemaleShareSum = 0;
        $maleShareSum = 0;
        $femaleShareSum = 0;

        $statistics = [
            'totalAccounts' => $this->getTotalAccounts($filteredUsers, $startDate, $endDate),
            'totalMembers' => $filteredUsers->count(),
            'femaleMembersCount' => $femaleUsers->count(),
            'refugesMemberCount' => $refuges->count(),
            'maleMembersCount' => $maleUsers->count(),
            'youthMembersCount' => $youthUsers->count(),
            'pwdMembersCount' => $pwdUsers->count(),
            'refugeeMaleSavings' => $refugeMaleShareSum,
            'refugeeFemaleSavings' => $refugeFemaleShareSum,
            'pwdMaleSavings' => $pwdMaleShareSum,
            'pwdFemaleSavings' => $pwdFemaleShareSum,
            'maleTotalBalance' => $this->getTotalBalance($maleUsers, 'SHARE', $startDate, $endDate),
            'femaleTotalBalance' => $this->getTotalBalance($femaleUsers, 'SHARE', $startDate, $endDate),
            'refugeeMaleLoans' => $this->getLoanSumForGender($refuges, 'Male', $startDate, $endDate),
            'refugeeFemaleLoans' => $this->getLoanSumForGender($refuges, 'Female', $startDate, $endDate),
            'pwdMaleLoans' => $this->getLoanSumForGender($pwdUsers, 'Male', $startDate, $endDate),
            'pwdFemaleLoans' => $this->getLoanSumForGender($pwdUsers, 'Female', $startDate, $endDate),
            'youthTotalBalance' => $this->getTotalBalance($youthUsers, 'SHARE', $startDate, $endDate),
            'pwdTotalBalance' => $this->getTotalBalance($pwdUsers, 'SHARE', $startDate, $endDate),
            'totalLoanAmount' => $this->getTotalLoanAmount($filteredUsers, $startDate, $endDate),
            'loanSumForWomen' => $this->getLoanSumForGender($filteredUsers, 'Female', $startDate, $endDate),
            'loanSumForMen' => $this->getLoanSumForGender($filteredUsers, 'Male', $startDate, $endDate),
            'loanSumForYouths' => $this->getLoanSumForYouths($filteredUsers, $startDate, $endDate),
            'pwdTotalLoanBalance' => $this->getTotalLoanBalance($pwdUsers, $startDate, $endDate),
        ];

        return $this->generateCsv($statistics, $startDate, $endDate);
    }

    private function getTotalAccounts($filteredUsers, $startDate, $endDate)
    {
        // Get the distinct Sacco IDs from the filtered users
        $saccoIds = $filteredUsers->pluck('sacco_id')->unique();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        // Query to count Saccos
        $query = Sacco::whereIn('id', $saccoIds);

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalAccounts = $query->count();

        return $totalAccounts;
    }



    private function getTotalBalance($users, $type, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        // Use pluck to extract only the IDs from the $users collection
        $userIds = $users->pluck('id')->toArray();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        $query = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)  // Use the extracted user IDs
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('t.type', $type);  // Use the specified transaction type

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('t.created_at', [$startDate, $endDate]); // Filter by created_at date range
        }

        $totalBalance = $query->where(function ($query) {
                $query->whereNull('users.user_type')
                    ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.amount) as total_balance'))
            ->first()
            ->total_balance;

        return $totalBalance;
    }

    private function getTotalLoanAmount($users, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        // Use pluck to extract only the IDs from the $users collection
        $userIds = $users->pluck('id')->toArray();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        $query = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)  // Use the extracted user IDs
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('t.type', 'LOAN');  // Loan type transactions

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('t.created_at', [$startDate, $endDate]); // Filter by created_at date range
        }

        $totalLoanAmount = $query->where(function ($query) {
                $query->whereNull('users.user_type')
                    ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.amount) as total_loan_amount'))
            ->first()
            ->total_loan_amount;

        return $totalLoanAmount;
    }

    private function getLoanSumForGender($users, $gender, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        // Extract user IDs from the $users collection
        $userIds = $users->pluck('id')->toArray();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        $query = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('users.sex', $gender)
            ->where('t.type', 'LOAN');

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('t.created_at', [$startDate, $endDate]);
        }

        $loanSumForGender = $query->where(function ($query) {
                $query->whereNull('users.user_type')
                    ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.amount) as total_loan_amount'))
            ->first()
            ->total_loan_amount;

        return $loanSumForGender;
    }

    private function getLoanSumForYouths($users, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        // Filter to get only youth users
        $youthUsers = $users->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });

        // Extract user IDs from the filtered collection
        $youthUserIds = $youthUsers->pluck('id')->toArray();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        $query = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $youthUserIds)
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('t.type', 'LOAN');

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('t.created_at', [$startDate, $endDate]);
        }

        $loanSumForYouths = $query->where(function ($query) {
                $query->whereNull('users.user_type')
                    ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.amount) as total_loan_amount'))
            ->first()
            ->total_loan_amount;

        return $loanSumForYouths;
    }

    private function getTotalLoanBalance($users, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

        // Extract user IDs from the $users collection
        $userIds = $users->pluck('id')->toArray();

        // Check if we're using all-time data
        $isAllTimeData = false;
        $oldestRecord = User::orderBy('created_at', 'asc')->first();
        if ($oldestRecord && $startDate->lte(Carbon::parse($oldestRecord->created_at)) && $endDate->gte(Carbon::now())) {
            $isAllTimeData = true;
        }

        $query = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('t.type', 'LOAN');

        // Only apply date filter if not using all-time data
        if (!$isAllTimeData) {
            $query->whereBetween('t.created_at', [$startDate, $endDate]);
        }

        $totalLoanBalance = $query->where(function ($query) {
                $query->whereNull('users.user_type')
                    ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.balance) as total_loan_balance'))
            ->first()
            ->total_loan_balance;

        return $totalLoanBalance;
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

            // Write BOM for UTF-8 encoding
            fwrite($file, "\xEF\xBB\xBF");

            $data = [
                // Counts
                ['Metric', 'Value (UGX)'],
                ['Total Number of Groups Registered', $statistics['totalAccounts']],
                ['Total Number of Members', $statistics['totalMembers']],
                ['Number of Members by Gender', ''],
                ['  Female', $statistics['femaleMembersCount']],
                ['  Male', $statistics['maleMembersCount']],
                ['  Refugees', $statistics['refugesMemberCount']],
                ['Number of Youth Members', $statistics['youthMembersCount']],
                ['Number of PWDs', $statistics['pwdMembersCount']],

                // Savings
                ['Savings by Gender', ''],
                ['  Female', $this->formatCurrency($statistics['femaleTotalBalance'])],
                ['  Male', $this->formatCurrency($statistics['maleTotalBalance'])],
                ['Savings by Youth', $this->formatCurrency($statistics['youthTotalBalance'])],
                ['Savings by Refugees', ''],
                ['  Female Refugees', $this->formatCurrency($statistics['refugeeFemaleSavings'])],
                ['  Male Refugees', $this->formatCurrency($statistics['refugeeMaleSavings'])],
                ['Savings by PWDs', ''],
                ['  Female PWDs', $this->formatCurrency($statistics['pwdFemaleSavings'])],
                ['  Male PWDs', $this->formatCurrency($statistics['pwdMaleSavings'])],
                ['Savings by PWDs (Overall)', $this->formatCurrency($statistics['pwdTotalBalance'])],

                // Loans
                ['Total Loans', $this->formatCurrency($statistics['totalLoanAmount'])],
                ['Loans by Gender', ''],
                ['  Female', $this->formatCurrency($statistics['loanSumForWomen'])],
                ['  Male', $this->formatCurrency($statistics['loanSumForMen'])],
                ['Loans by Youth', $this->formatCurrency($statistics['loanSumForYouths'])],
                ['Loans by Refugees', ''],
                ['  Female Refugees', $this->formatCurrency($statistics['refugeeFemaleLoans'])],
                ['  Male Refugees', $this->formatCurrency($statistics['refugeeMaleLoans'])],
                ['Loans by PWDs', ''],
                ['  Female PWDs', $this->formatCurrency($statistics['pwdFemaleLoans'])],
                ['  Male PWDs', $this->formatCurrency($statistics['pwdMaleLoans'])],
                ['Loans by PWDs (Overall)', $this->formatCurrency($statistics['pwdTotalLoanBalance'])],
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

    // Helper function to format currency values
    private function formatCurrency($amount)
    {
        return 'UGX ' . number_format(abs($amount), 2);
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

        // $totalAccounts = Sacco::whereHas('users', function ($query) {
        //     $query->whereHas('position', function ($query) {
        //         $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
        //     })->whereNotNull('phone_number')
        //         ->whereNotNull('name');
        // })
        // ->whereNotIn('status', ['deleted', 'inactive'])
        // ->count();

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

        $admin = Admin::user();
        $adminId = $admin->id;
        $selectedOrgId = request()->get('selected_org');

        if (!$admin->inRoles(['admin', 'administrator'])) {
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
            $adminRegion = trim($orgAllocation->region);
            $orgName = $organization->name;
            $logoUrl = '';

            if ($organization->name === 'International Institute of Rural Reconstruction (IIRR)') {
                $logoUrl = 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png';
            } elseif ($organization->name === 'Ripple Effect Uganda') {
                $logoUrl = 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png';
            }

            $organizationContainer = '<div style="text-align: center; padding-bottom: 25px;"><img src="' . $logoUrl . '" alt="' . $organization->name . '" class="img-fluid rounded-circle" style="max-width: 200px;"></div>';

            if (empty($adminRegion)) {
                $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)
                    ->pluck('sacco_id')
                    ->toArray();

                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgIds)
                    ->pluck('vsla_organisation_id')
                    ->toArray();
            } else {
                // Get all Saccos in the admin's region (case-insensitive comparison)
                $saccoIds = VslaOrganisationSacco::join('saccos', 'vsla_organisation_sacco.sacco_id', '=', 'saccos.id')
                    ->where('vsla_organisation_sacco.vsla_organisation_id', $orgIds)
                    ->whereRaw('LOWER(saccos.district) = ?', [strtolower($adminRegion)])
                    ->pluck('sacco_id')
                    ->toArray();

                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgIds)
                    ->where('region', $adminRegion)
                    ->pluck('vsla_organisation_id')
                    ->toArray();
            }

            $totalOrgAdmins = count($OrgAdmins);
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
            $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->whereIn('sacco_id', $saccoIds)->filter(function ($user) {
                return Carbon::parse($user->dob)->age < 35;
            })->count() / $totalMembers * 100 : 0;

            // Get refugee users by gender
            // $refugeMaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
            // ->where('refugee_status', 'yes')
            // ->where('sex', 'Male');
            // $refugeFemaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
            // ->where('refugee_status', 'yes')
            // ->where('sex', 'Female');

            // Get refugee savings by gender
            $refugeMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $refugeFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // Get refugee male loan count
            $refugeeMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get refugee female loan count
            $refugeeFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get refugee male loan amount
            $refugeeMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get refugee female loan amount
            $refugeeFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // PWDs account dissermination
            $pwdMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // Get pwd male loan count
            $pwdMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get pwd female loan count
            $pwdFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get pwd male loan amount
            $pwdMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get pwd female loan amount
            $pwdFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');


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
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->count();

            $loansDisbursedToMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
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
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $loanSumForMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdTotalLoanBalance = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
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

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $transactions = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->whereIn('saccos.id', $saccoIds)
                // ->where('users.sex', 'Male')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('transactions.type', 'SHARE') // Filter for 'SHARE' type transactions
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select('transactions.*') // Select all transaction fields
                ->get();
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
                    $query->whereNotIn('status', ['deleted', 'inactive']);
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

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $youthTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereIn('users.id', $youthIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            $pwdTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.pwd', 'Yes')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for male users across all groups (excluding deleted and inactive Saccos and Admins)
            $maleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.sex', 'Male')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for female users across all groups (excluding deleted and inactive Saccos and Admins)
            $femaleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.sex', 'Female')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Display the results
            // dd(['male_total_balance' => $maleTotalBalance, 'female_total_balance' => $femaleTotalBalance]);;

            // $topSavingGroups = User::where('user_type', 'Admin')->whereIn('sacco_id', $saccoIds)->get()->sortByDesc('balance')->take(6);
        }
        else
        if ($selectedOrgId) {
            // When an organization is selected, filter data accordingly
            $organization = VslaOrganisation::find($selectedOrgId);
            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $selectedOrgId)
                ->pluck('sacco_id')
                ->toArray();

            $organizationContainer = '';

            // Update filtered users to only include those from selected organization's SACCOs
            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);
            $orgName = $organization->name;

            // Update other metrics for the selected organization
            $totalSaccos = Sacco::whereIn('id', $saccoIds)->count();
            $organisationCount = 1;
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
            $youthMembersPercentage = ($totalMembers > 0) ? $filteredUsers->whereIn('sacco_id', $saccoIds)->filter(function ($user) {
                return Carbon::parse($user->dob)->age < 35;
            })->count() / $totalMembers * 100 : 0;

            $refugeMaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
                ->where('refugee_status', 'yes')
                ->where('sex', 'Male');
            $refugeFemaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
                ->where('refugee_status', 'yes')
                ->where('sex', 'Female');

            // Get refugee savings by gender
            $refugeMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $refugeFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $refugeeMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get refugee female loan count
            $refugeeFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get refugee male loan amount
            $refugeeMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get refugee female loan amount
            $refugeeFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // PWDs disermination by gender
            $pwdMaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
                ->where('pwd', 'yes')
                ->where('sex', 'Male');
            $pwdFemaleUsers = $filteredUsers->whereIn('sacco_id', $saccoIds)
                ->where('pwd', 'yes')
                ->where('sex', 'Female');

            // Get pwd savings by gender
            $pwdMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $pwdMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get pwd female loan count
            $pwdFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get pwd male loan amount
            $pwdMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get pwd female loan amount
            $pwdFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

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
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->count();

            $loansDisbursedToMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
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
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            $loanSumForMen = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'LOAN')
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdTotalLoanBalance = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->whereIn('transactions.sacco_id', $saccoIds)
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

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $transactions = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->whereIn('saccos.id', $saccoIds)
                // ->where('users.sex', 'Male')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('transactions.type', 'SHARE') // Filter for 'SHARE' type transactions
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select('transactions.*') // Select all transaction fields
                ->get();
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
                    $query->whereNotIn('status', ['deleted', 'inactive']);
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

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $youthTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->whereIn('users.id', $youthIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            $pwdTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.pwd', 'Yes')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for male users across all groups (excluding deleted and inactive Saccos and Admins)
            $maleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.sex', 'Male')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for female users across all groups (excluding deleted and inactive Saccos and Admins)
            $femaleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.sacco_id', $saccoIds)
                ->where('users.sex', 'Female')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Display the results
            // dd(['male_total_balance' => $maleTotalBalance, 'female_total_balance' => $femaleTotalBalance]);;

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

            // $refugeMaleUsers = $filteredUsers->where('refugee_status', 'yes')
            // ->where('sex', 'Male');
            // $refugeFemaleUsers = $filteredUsers->where('refugee_status', 'yes')
            // ->where('sex', 'Female');

            // dd(
            //     Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            //         // ->whereIn('transactions.sacco_id', $saccoIds)
            //         ->where('transactions.type', 'SHARE')
            //         ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
            //         ->where('users.sex', 'Male')
            //         ->select('transactions.amount', 'transactions.type', 'users.first_name', 'users.last_name', 'users.refugee_status', 'users.sex')
            //         ->get()
            // );

            // Get refugee savings by gender
            $refugeMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                // ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $refugeFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                // ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // Get refugee male loan count
            $refugeeMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get refugee female loan count
            $refugeeFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get refugee male loan amount
            $refugeeMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get refugee female loan amount
            $refugeeFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // PWDs dissermination by gender
            $pwdMaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                // ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            $pwdFemaleShareSum = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                // ->whereIn('transactions.sacco_id', $saccoIds)
                ->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

            // Get pwd male loan count
            $pwdMaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->count();

            // Get pwd female loan count
            $pwdFemaleLoanCount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->count();

            // Get pwd male loan amount
            $pwdMaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount');

            // Get pwd female loan amount
            $pwdFemaleLoanAmount = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->where('transactions.type', 'LOAN')
                ->whereRaw('LOWER(users.pwd) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount');

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

            $saccoIdsWithPositions = User::whereHas('sacco', function ($query) {
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

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $transactions = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
                ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('transactions.type', 'SHARE') // Filter for 'SHARE' type transactions
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select('transactions.*') // Select all transaction fields
                ->get();
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

            $topSavingGroups = User::where('user_type', 'Admin')
                ->whereHas('sacco', function ($query) {
                    $query->whereNotIn('status', ['deleted', 'inactive']);
                })
                ->get()
                ->sortByDesc('balance')
                ->take(6);
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

            $filteredUsersIds = $filteredUsers->pluck('id')->toArray();

            // dd($filteredUsersIds);

            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

            $youthTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->whereIn('users.id', $youthIds)
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            $pwdTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->where('users.pwd', 'Yes')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for male users across all groups (excluding deleted and inactive Saccos and Admins)
            $maleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->where('users.sex', 'Male')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;

            // Fetch total balances for female users across all groups (excluding deleted and inactive Saccos and Admins)
            $femaleTotalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
                ->join('saccos as s', 'users.sacco_id', '=', 's.id')
                ->where('users.sex', 'Female')
                ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
                ->where('t.type', 'SHARE')
                ->where(function ($query) {
                    $query->whereNull('users.user_type')
                        ->orWhere('users.user_type', '<>', 'Admin');
                })
                ->select(DB::raw('SUM(t.amount) as total_balance'))
                ->first()
                ->total_balance;
        }
        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();
        // $femaleTotalBalance = number_format($femaleUsers->sum('balance'), 2);

        // dd($femaleTotalBalance);

        $maleUsers = $filteredUsers->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();
        // $maleTotalBalance = number_format($maleUsers->sum('balance'), 2);

        $refugeMaleUsers = $maleUsers->where('refugee_status', 'Yes');
        $refugeMaleUsersCount = $refugeMaleUsers->count();
        $refugeFemaleUsers = $femaleUsers->where('refugee_status', 'Yes');
        $refugeFemaleUsersCount = $refugeFemaleUsers->count();

        // PWDs disermination by gender
        $pwdMaleUsers = $maleUsers->where('pwd', 'Yes');
        $pwdMaleUsersCount = $pwdMaleUsers->count();
        $pwdFemaleUsers = $femaleUsers->where('pwd', 'Yes');
        $pwdFemaleUsersCount = $pwdFemaleUsers->count();

        // dd([
        //     'all_male_count' => $maleUsers->count(),
        //     'all_female_count' => $femaleUsers->count(),
        //     'refugee_male_count' => $refugeMaleUsers->count(),
        //     'refugee_female_count' => $refugeFemaleUsers->count(),
        //     'refugee_male_details' => $refugeMaleUsers->map(function($user) {
        //         return [
        //             'first_name' => $user->first_name,
        //             'last_name' => $user->last_name,
        //             'refugee_status' => $user->refugee_status,
        //             'sex' => $user->sex
        //         ];
        //     }),
        //     'refugee_female_details' => $refugeFemaleUsers->map(function($user) {
        //         return [
        //             'first_name' => $user->first_name,
        //             'last_name' => $user->last_name,
        //             'refugee_status' => $user->refugee_status,
        //             'sex' => $user->sex
        //         ];
        //     })
        // ]);

        $youthUsers = $filteredUsers->filter(function ($user) {
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

        // Retrieve the user with the Sacco information
        // $cliff_group = User::where('last_name', 'Dairy')
        // ->where('first_name', 'maendeleo')
        // ->with('sacco')
        // ->get();

        // // $group = Sacco::where('name', 'rwamahega')->first();

        // // // Check if the collection is not empty
        // if ($cliff_group->isNotEmpty()) {
        //     // Get the first user from the collection
        //     $user = $cliff_group->first();

        //     // Check if the user has an associated Sacco
        //     if ($user->sacco) {
        //         // Update the Sacco's status to "inactive"
        //         $user->sacco->status = 'active';
        //         $user->sacco->save();

        //         echo "Sacco status updated to active.";
        //     } else {
        //         echo "User does not have an associated Sacco.";
        //     }

        //     // Delete the user
        //     // $user->delete();
        //     echo "User deleted successfully.";
        // } else {
        //     echo "User not found.";
        // }

        // dd($cliff_group);

        $admin = Admin::user();
        $adminId = $admin->id;

        // Add organization selector for admin users
        $organizationSelector = '';
        if ($admin->isRole('admin')) {
            $organizations = VslaOrganisation::all();
            $organizationSelector = '
        <div style="text-align: right; margin-bottom: 20px;">
            <form id="orgSelectForm" method="GET" style="display: flex; gap: 15px; justify-content: flex-end; align-items: center;">
                <select name="selected_org" id="orgSelect" style="
                    padding: 12px 20px;
                    border: 2px solid #e2e8f0;
                    border-radius: 12px;
                    font-size: 16px;
                    color: #4a5568;
                    min-width: 200px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                    transition: all 0.3s ease;">
                    <option value="">All Organizations</option>';

            foreach ($organizations as $org) {
                $selected = request()->get('selected_org') == $org->id ? 'selected' : '';
                $organizationSelector .= '<option value="' . $org->id . '" ' . $selected . '>' . $org->name . '</option>';
            }

            $organizationSelector .= '
                </select>
            </form>
            <script>
                document.getElementById("orgSelect").addEventListener("change", function() {
                    document.getElementById("orgSelectForm").submit();
                });
            </script>
        </div>';
        }

        // Modify the existing organization filtering logic
        if (!$admin->isRole('admin')) {
            // Existing non-admin logic...
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
            $adminRegion = trim($orgAllocation->region);  // Get admin's region
            $orgName = $organization->name;

            // Add region-based filtering
            if (empty($adminRegion)) {
                // If no region is specified, get all SACCOs for the organization
                $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)
                    ->pluck('sacco_id')
                    ->toArray();
            } else {
                // Get only SACCOs in the admin's region
                $saccoIds = VslaOrganisationSacco::join('saccos', 'vsla_organisation_sacco.sacco_id', '=', 'saccos.id')
                    ->where('vsla_organisation_sacco.vsla_organisation_id', $orgIds)
                    ->whereRaw('LOWER(saccos.district) = ?', [strtolower($adminRegion)])
                    ->pluck('sacco_id')
                    ->toArray();
            }

            // Filter users based on the SACCO IDs
            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);

        } else {
            // Modified admin logic to handle organization selection
            $selectedOrgId = request()->get('selected_org');
            if ($selectedOrgId) {
                $organization = VslaOrganisation::find($selectedOrgId);
                $orgIds = $selectedOrgId;
                $orgName = $organization->name;
                // Apply organization-specific filtering
                $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)->pluck('sacco_id')->toArray();
                $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);
            } else {
                $orgName = 'DigiSave VSLA Platform';
                // Use existing all-organization logic...
            }
        }
        // if (!$admin->isRole('admin')) {
        //     // Existing non-admin logic...
        //     $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
        //     if (!$orgAllocation) {
        //         Auth::logout();
        //         $message = "You are not allocated to any organization. Please contact M-Omulimisa Service Help for assistance.";
        //         Session::flash('warning', $message);
        //         admin_error($message);
        //         return redirect('auth/logout');
        //     }
        //     $organization = VslaOrganisation::find($orgAllocation->vsla_organisation_id);
        //     $orgIds = $orgAllocation->vsla_organisation_id;
        //     $orgName = $organization->name;
        // } else {
        //     // Modified admin logic to handle organization selection
        //     $selectedOrgId = request()->get('selected_org');
        //     if ($selectedOrgId) {
        //         $organization = VslaOrganisation::find($selectedOrgId);
        //         $orgIds = $selectedOrgId;
        //         $orgName = $organization->name;
        //         // Apply organization-specific filtering
        //         $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)->pluck('sacco_id')->toArray();
        //         $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);
        //     } else {
        //         $orgName = 'DigiSave VSLA Platform';
        //         // Use existing all-organization logic...
        //     }
        // }

        // $loanSumForWomen = (float)($loanSumForWomen ?? 0);
        // $loanSumForMen = (float)($loanSumForMen ?? 0);
        // $loanSumForYouths = (float)($loanSumForYouths ?? 0);
        // $pwdTotalLoanBalance = (float)($pwdTotalLoanBalance ?? 0);
        // $refugeeMaleLoanAmount = (float)($refugeeMaleLoanAmount ?? 0);
        // $refugeeFemaleLoanAmount = (float)($refugeeFemaleLoanAmount ?? 0);
        // $femaleTotalBalance = (float)($femaleTotalBalance ?? 0);
        // $maleTotalBalance = (float)($maleTotalBalance ?? 0);
        // $youthTotalBalance = (float)($youthTotalBalance ?? 0);
        // $pwdTotalBalance = (float)($pwdTotalBalance ?? 0);
        // $refugeMaleShareSum = (float)($refugeMaleShareSum ?? 0);
        // $refugeFemaleShareSum = (float)($refugeFemaleShareSum ?? 0);

        return $content
            ->header('<div style="
            text-align: center;
            background: linear-gradient(120deg, #1a472a, #2e8b57);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin: 20px 0;
            box-shadow: 0 10px 25px rgba(46, 139, 87, 0.2);
            letter-spacing: 1px;">
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
                ' . $orgName . '
            </div>
            ' . (!$admin->isRole('admin') && !empty($adminRegion) ? '
            <div style="
                font-size: 20px;
                color: rgba(255, 255, 255, 0.9);
                margin-top: 10px;
                font-weight: 500;">
                ' . ucfirst($adminRegion) . ' District
            </div>' : '') . '
        </div>')
            ->body(

                $organizationSelector .
                    $organizationContainer .
                    '<div style="
                    background: linear-gradient(135deg, #fff, #f0f7f4);
                    padding: 30px;
                    border-radius: 20px;
                    margin-bottom: 30px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.8);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h2 style="
                                margin: 0;
                                font-size: 32px;
                                font-weight: 800;
                                background: linear-gradient(120deg, #1a472a, #2e8b57);
                                -webkit-background-clip: text;
                                -webkit-text-fill-color: transparent;">
                            Welcome back, ' . $userName . '!
                        </h2>
                        <div id="quote-slider" style="
                                margin: 12px 0 0;
                                font-size: 18px;
                                color: #4a5568;
                                height: 28px;
                                font-style: italic;">
                            <p style="transition: all 0.5s ease;">' . $quotes[0] . '</p>
                        </div>
                    </div>
                    <div>
                        <img src="https://www.pngmart.com/files/21/Admin-Profile-PNG-Clipart.png"
                             alt="Welcome Image"
                             style="
                                height: 120px;
                                border-radius: 50%;
                                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                                border: 4px solid #fff;
                                transition: transform 0.3s ease;"
                             onmouseover="this.style.transform=\'scale(1.05)\'"
                             onmouseout="this.style.transform=\'scale(1)\'">
                    </div>
                </div>
            </div>' .
                    '<div style="text-align: right; margin-bottom: 30px;">
                <form action="' . route(config('admin.route.prefix') . '.export-data') . '"
                      method="GET"
                      style="display: flex; gap: 15px; justify-content: flex-end; align-items: center;">
                    <input type="date"
                           name="start_date"
                           required
                           style="
                                padding: 12px 20px;
                                border: 2px solid #e2e8f0;
                                border-radius: 12px;
                                font-size: 16px;
                                color: #4a5568;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                                transition: all 0.3s ease;">
                    <input type="date"
                           name="end_date"
                           required
                           style="
                                padding: 12px 20px;
                                border: 2px solid #e2e8f0;
                                border-radius: 12px;
                                font-size: 16px;
                                color: #4a5568;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                                transition: all 0.3s ease;">
                    <button type="submit"
                            class="btn btn-primary"
                            style="
                                padding: 12px 30px;
                                background: linear-gradient(120deg, #1a472a, #2e8b57);
                                border: none;
                                border-radius: 12px;
                                color: white;
                                font-weight: 600;
                                font-size: 16px;
                                box-shadow: 0 8px 15px rgba(46, 139, 87, 0.2);
                                transition: all 0.3s ease;
                                cursor: pointer;"
                            onmouseover="this.style.transform=\'translateY(-2px)\'"
                            onmouseout="this.style.transform=\'translateY(0)\'">
                        Export Data
                    </button>
                </form>
            </div>' .
                    '<div style="
                    background: linear-gradient(135deg, #f7faf9, #ffffff);
                    padding: 30px;
                    border-radius: 20px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.8);">' .
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
                        'youthTotalBalance' => number_format($youthTotalBalance),
                        'pwdMembersCount' => $pwdMembersCount,
                        // 'pwdTotalBalance' => number_format($pwdTotalBalance),
                        'refugeeMaleMembersCount' => $refugeMaleUsersCount,
                        'refugeeFemaleMembersCount' => $refugeFemaleUsersCount,
                        'refugeeMaleSavings' => number_format($refugeMaleShareSum, 2),
                        'refugeeFemaleSavings' => number_format($refugeFemaleShareSum, 2),
                        // PWDs dissermination by gender

                        'pwdMaleMembersCount' => $pwdMaleUsersCount,
                        'pwdFemaleMembersCount' => $pwdFemaleUsersCount,
                        'pwdMaleSavings' => number_format($pwdMaleShareSum, 2),
                        'pwdFemaleSavings' => number_format($pwdFemaleShareSum, 2)
                    ]) .
                    '<div style="
                    background: linear-gradient(135deg, #f7faf9, #ffffff);
                    padding: 30px;
                    border-radius: 20px;
                    margin-top: 30px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.8);">' .
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
                        'pwdTotalLoanBalance' => $pwdTotalLoanBalance,
                        'refugeeMaleLoanCount' => $refugeeMaleLoanCount,
                        'refugeeFemaleLoanCount' => $refugeeFemaleLoanCount,
                        'refugeeMaleLoanAmount' => $refugeeMaleLoanAmount,
                        'refugeeFemaleLoanAmount' => $refugeeFemaleLoanAmount,
                        // PWDs dissermination by gender

                        'pwdMaleLoanCount' => $pwdMaleLoanCount,
                        'pwdFemaleLoanCount' => $pwdFemaleLoanCount,
                        'pwdMaleLoanAmount' => $pwdMaleLoanAmount,
                        'pwdFemaleLoanAmount' => $pwdFemaleLoanAmount
                    ]) .
                    '</div>' .
                    view('widgets.chart_container', [
                        'monthYearList' => $monthYearList,
                        'totalSavingsList' => $totalSavingsList,
                    ]) .
                    '<div class="row" style="padding-top: 35px;">
                <div class="col-md-6" style="
                        background: white;
                        padding: 25px;
                        border-radius: 15px;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    ' . view('widgets.top_saving_groups', [
                        'topSavingGroups' => $topSavingGroups,
                    ]) . '
                </div>
                <div class="col-md-6" style="
                        background: white;
                        padding: 25px;
                        border-radius: 15px;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
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

        setInterval(showNextQuote, 3000);
    });
</script>