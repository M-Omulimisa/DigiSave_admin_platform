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

/**
 * Trait FiltersByAdminRegion
 *
 * Provides methods to filter SACCOs by the admin's assigned organization and region.
 */
trait FiltersByAdminRegion
{
    /**
     * Returns an array of SACCO IDs based on the admin’s organization and region.
     * For super admins, returns null (no filtering).
     */
    protected function getFilteredSaccoIds()
    {
        $admin = Admin::user();
        $adminId = $admin->id;

        // If super admin, no filtering needed
        if ($admin->isRole('admin')) {
            return null;
        }

        $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
        if (!$orgAllocation) {
            return [];
        }

        $orgIds = $orgAllocation->vsla_organisation_id;
        $adminRegion = trim($orgAllocation->region);

        // If no region specified, return all SACCOs for the organization
        if (empty($adminRegion)) {
            return VslaOrganisationSacco::where('vsla_organisation_id', $orgIds)
                ->pluck('sacco_id')
                ->toArray();
        }

        // Return SACCOs filtered by both organization and region (district)
        return VslaOrganisationSacco::join('saccos', 'vsla_organisation_sacco.sacco_id', '=', 'saccos.id')
            ->where('vsla_organisation_sacco.vsla_organisation_id', $orgIds)
            ->whereRaw('LOWER(saccos.district) = ?', [strtolower($adminRegion)])
            ->pluck('sacco_id')
            ->toArray();
    }

    /**
     * Optionally applies a region filter (by SACCO IDs) to the given query.
     */
    protected function applyRegionFilter($query, $saccoIds)
    {
        if ($saccoIds === null) {
            return $query;
        }
        return $query->whereIn('sacco_id', $saccoIds);
    }

    /**
     * Returns various transaction statistics filtered by SACCO IDs (and an optional date range).
     */
    protected function getTransactionStats($saccoIds, $startDate = null, $endDate = null)
    {
        $query = DB::table('transactions')
            ->join('users', 'transactions.source_user_id', '=', 'users.id')
            ->join('saccos', 'users.sacco_id', '=', 'saccos.id')
            ->whereNotIn('saccos.status', ['deleted', 'inactive'])
            ->where(function ($q) {
                $q->whereNull('users.user_type')
                  ->orWhere('users.user_type', '<>', 'Admin');
            });

        if ($saccoIds !== null) {
            $query->whereIn('users.sacco_id', $saccoIds);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transactions.created_at', [$startDate, $endDate]);
        }

        return [
            'male_share_sum' => $query->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Male')
                ->sum('transactions.amount'),

            'female_share_sum' => $query->where('transactions.type', 'SHARE')
                ->where('users.sex', 'Female')
                ->sum('transactions.amount'),

            'pwd_share_sum' => $query->where('transactions.type', 'SHARE')
                ->where('users.pwd', 'yes')
                ->sum('transactions.amount'),

            'youth_share_sum' => $query->where('transactions.type', 'SHARE')
                ->whereDate('users.dob', '>', now()->subYears(35))
                ->sum('transactions.amount'),

            'refugee_male_share_sum' => $query->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Male')
                ->sum('transactions.amount'),

            'refugee_female_share_sum' => $query->where('transactions.type', 'SHARE')
                ->whereRaw('LOWER(users.refugee_status) = ?', ['yes'])
                ->where('users.sex', 'Female')
                ->sum('transactions.amount')
        ];
    }
}

class HomeController extends Controller
{
    use FiltersByAdminRegion;

    public function exportData(Request $request)
    {
        // Clear any output buffers to ensure no HTML/JS is included
        while (ob_get_level()) {
            ob_end_clean();
        }

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        // Validate date inputs
        if (!$startDate || !$endDate) {
            return redirect()->back()->withErrors(['error' => 'Both start and end dates are required.']);
        }

        $admin = Admin::user();
        $adminId = $admin->id;

        $users = User::all();

        // Apply date filter and other user type restrictions
        $filteredUsers = $users->filter(function ($user) use ($startDate, $endDate, $adminId) {
            $createdAt = Carbon::parse($user->created_at);
            return $createdAt->between($startDate, $endDate) &&
                $user->id !== $adminId &&
                !in_array($user->user_type, ['Admin', '4', '5']);
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

            // Use the trait to get SACCO IDs filtered by the admin's region.
            $saccoIds = $this->getFilteredSaccoIds();
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

            // Retrieve and sum up transactions for filtered users and specified SACCOs within the date range
            $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');

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
                ->sum('transactions.amount');

            // (Additional transaction sums for gender, refugee, pwd, loans, etc. remain unchanged.)
            // ...
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

        $statistics = [
            'totalAccounts' => $this->getTotalAccounts($filteredUsers, $startDate, $endDate),
            'totalMembers' => $filteredUsers->count(),
            'femaleMembersCount' => $femaleUsers->count(),
            'refugesMemberCount' => $refuges->count(),
            'maleMembersCount' => $maleUsers->count(),
            'youthMembersCount' => $youthUsers->count(),
            'pwdMembersCount' => $pwdUsers->count(),
            // Additional statistics and calculations…
            'refugeeMaleSavings' => 0, // placeholder for your calculation
            'refugeeFemaleSavings' => 0,
            'pwdMaleSavings' => 0,
            'pwdFemaleSavings' => 0,
            'maleTotalBalance' => 0,
            'femaleTotalBalance' => 0,
            'refugeeMaleLoans' => 0,
            'refugeeFemaleLoans' => 0,
            'pwdMaleLoans' => 0,
            'pwdFemaleLoans' => 0,
            'youthTotalBalance' => 0,
            'pwdTotalBalance' => 0,
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
        $saccoIds = $filteredUsers->pluck('sacco_id')->unique();

        $totalAccounts = Sacco::whereIn('id', $saccoIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return $totalAccounts;
    }

    private function getTotalBalance($users, $type, $startDate, $endDate)
    {
        $deletedOrInactiveSaccoIds = Sacco::whereIn('status', ['deleted', 'inactive'])->pluck('id');
        $userIds = $users->pluck('id')->toArray();

        $totalBalance = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->whereNotIn('users.sacco_id', $deletedOrInactiveSaccoIds)
            ->where('t.type', $type)
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->where(function ($query) {
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
        $userIds = $users->pluck('id')->toArray();

        $totalLoanAmount = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->where('t.type', 'LOAN')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->where(function ($query) {
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
        $userIds = $users->pluck('id')->toArray();

        $totalLoanAmount = User::join('transactions as t', 'users.id', '=', 't.source_user_id')
            ->join('saccos as s', 'users.sacco_id', '=', 's.id')
            ->whereIn('users.id', $userIds)
            ->where('users.sex', $gender)
            ->where('t.type', 'LOAN')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereNull('users.user_type')
                      ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->select(DB::raw('SUM(t.amount) as total_loan_amount'))
            ->first()
            ->total_loan_amount;

        return $totalLoanAmount;
    }

    private function getLoanSumForYouths($users, $startDate, $endDate)
    {
        $userIds = $users->pluck('id')->toArray();

        $loanSumForYouths = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->whereIn('users.id', $userIds)
            ->where('transactions.type', 'LOAN')
            ->whereDate('users.dob', '>', now()->subYears(35))
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereNull('users.user_type')
                      ->orWhere('users.user_type', '<>', 'Admin');
            })
            ->sum('transactions.amount');

        return $loanSumForYouths;
    }

    private function getTotalLoanBalance($users, $startDate, $endDate)
    {
        $userIds = $users->pluck('id')->toArray();

        $pwdTotalLoanBalance = Transaction::join('users', 'transactions.source_user_id', '=', 'users.id')
            ->whereIn('users.id', $userIds)
            ->where('transactions.type', 'LOAN')
            ->where('users.pwd', 'yes')
            ->whereBetween('users.created_at', [$startDate, $endDate])
            ->sum('transactions.amount');

        return $pwdTotalLoanBalance;
    }

    private function generateCsv($statistics, $startDate, $endDate)
    {
        $fileName = 'export_data_' . $startDate->toDateString() . '_to_' . $endDate->toDateString() . '.csv';
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
                ['Metric', 'Value (UGX)'],
                ['Total Number of Groups Registered', $statistics['totalAccounts'] ?? 0],
                // Add additional rows as needed…
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

    private function formatCurrency($amount)
    {
        return 'UGX ' . number_format(abs($amount), 2);
    }

    public function index(Content $content)
    {
        foreach (Sacco::where(["processed" => "no"])->get() as $sacco) {
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
            $adminRegion = trim($orgAllocation->region);
            $orgName = $organization->name;
            $logoUrl = '';

            if ($organization->name === 'International Institute of Rural Reconstruction (IIRR)') {
                $logoUrl = 'https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png';
            } elseif ($organization->name === 'Ripple Effect Uganda') {
                $logoUrl = 'https://referraldirectories.redcross.or.ke/wp-content/uploads/2023/01/ripple-effect-strapline.png';
            }

            $organizationContainer = '<div style="text-align: center; padding-bottom: 25px;"><img src="' . $logoUrl . '" alt="' . $organization->name . '" class="img-fluid rounded-circle" style="max-width: 200px;"></div>';

            // Use the trait to get the SACCO IDs filtered by region.
            $saccoIds = $this->getFilteredSaccoIds();

            $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgIds)
                ->pluck('vsla_organisation_id')
                ->toArray();

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

            // (Other calculations for refugee and pwd savings, loans, charts, etc. remain unchanged.)
        } else if ($selectedOrgId) {
            $organization = VslaOrganisation::find($selectedOrgId);
            $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $selectedOrgId)
                ->pluck('sacco_id')
                ->toArray();

            $organizationContainer = '';

            $filteredUsers = $filteredUsers->whereIn('sacco_id', $saccoIds);
            $orgName = $organization->name;

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

            // (Additional calculations for this branch remain unchanged.)
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

            // (Other calculations for this branch remain unchanged.)
        }

        $femaleUsers = $filteredUsers->where('sex', 'Female');
        $femaleMembersCount = $femaleUsers->count();

        $maleUsers = $filteredUsers->where('sex', 'Male');
        $maleMembersCount = $maleUsers->count();

        $refugeMaleUsers = $maleUsers->where('refugee_status', 'Yes');
        $refugeMaleUsersCount = $refugeMaleUsers->count();
        $refugeFemaleUsers = $femaleUsers->where('refugee_status', 'Yes');
        $refugeFemaleUsersCount = $refugeFemaleUsers->count();

        $pwdMaleUsers = $maleUsers->where('pwd', 'Yes');
        $pwdMaleUsersCount = $pwdMaleUsers->count();
        $pwdFemaleUsers = $femaleUsers->where('pwd', 'Yes');
        $pwdFemaleUsersCount = $pwdFemaleUsers->count();

        $youthUsers = $filteredUsers->filter(function ($user) {
            return Carbon::parse($user->dob)->age < 35;
        });
        $youthMembersCount = $youthUsers->count();

        $totalLoans = 0; // (Replace with your actual calculation)
        $percentageLoansWomen = 0;
        $percentageLoansMen = 0;
        $totalLoanSum = 0;
        $percentageLoanSumWomen = 0;
        $percentageLoanSumMen = 0;

        $quotes = [
            "Empowerment through savings and loans.",
            "Collaboration is key to success.",
            "Building stronger communities together.",
            "Savings groups transform lives.",
            "In unity, there is strength."
        ];

        $totalLoanAmount = 0; // (Replace with your actual calculation)

        $admin = Admin::user();
        $adminId = $admin->id;

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
        } else {
            $selectedOrgId = request()->get('selected_org');
            if ($selectedOrgId) {
                $organization = VslaOrganisation::find($selectedOrgId);
                $orgIds = $selectedOrgId;
                $orgName = $organization->name;
            } else {
                $orgName = 'DigiSave VSLA Platform';
            }
        }

        return $content
            ->header('<div style="
                    text-align: center;
                    background: linear-gradient(120deg, #1a472a, #2e8b57);
                    color: white;
                    font-size: 32px;
                    font-weight: bold;
                    padding: 30px;
                    border-radius: 20px;
                    margin: 20px 0;
                    box-shadow: 0 10px 25px rgba(46, 139, 87, 0.2);
                    letter-spacing: 1px;">
                    ' . $orgName . '
            ' . (!$admin->isRole('admin') && !empty($adminRegion) ? '
            <div style="
                font-size: 18px;
                margin-top: 10px;
                padding: 5px 15px;
                display: inline-block;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 15px;">
                Region: ' . ucfirst($adminRegion) . '
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
                        'refugeeMaleMembersCount' => $refugeMaleUsersCount,
                        'refugeeFemaleMembersCount' => $refugeFemaleUsersCount,
                        'refugeeMaleSavings' => number_format($refugeMaleShareSum, 2),
                        'refugeeFemaleSavings' => number_format($refugeFemaleShareSum, 2),
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
